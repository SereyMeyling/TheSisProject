<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Department;
// use App\Models\user;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Prescription;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|doctor|nurse']);
    }

    /**
     * Role-based view router for http://127.0.0.1:8000/doctor
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRole = 'doctor';

        if ($user) {
            if ($user->hasRole('admin')) {
                $userRole = 'admin';
            } elseif ($user->hasRole('nurse')) {
                $userRole = 'nurse';
            } elseif ($user->hasRole('doctor')) {
                $userRole = 'doctor';
            }
        }

        // ------------------------------------------------------------------
        // 1. ADMIN ROLE: Doctor Directory & Consultation Audit
        // ------------------------------------------------------------------
        if ($userRole === 'admin') {
            $query = User::role('doctor')->with('department');
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    // Search by doctor name
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        // Search by specialization
                        ->orWhere('specialization', 'LIKE', '%' . $search . '%')
                        // Search by phone
                        ->orWhere('phone', 'LIKE', '%' . $search . '%');
                    // Search by User ID if search is a number
                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                });
            }

            $doctors = $query->orderBy('id', 'desc')->paginate(10)->appends($request->query());

            $totalDoctors = User::role('doctor')->count();

            $activeDoctors = User::role('doctor')->count();

            $todayConsultations = MedicalRecord::whereDate('visit_date', today())->count();

            return view('form.doctor.index', compact(
                'userRole',
                'doctors',
                'totalDoctors',
                'activeDoctors',
                'todayConsultations'
            ));
        }

        // ------------------------------------------------------------------
        // 2. NURSE ROLE: Triage & Patient Vitals Preparation Queue
        // ------------------------------------------------------------------
        if ($userRole === 'nurse') {
            $query = MedicalRecord::with(['patient', 'doctor']);

            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $query->whereHas('patient', function ($q) use ($term) {
                    $q->where('full_name', 'LIKE', $term)
                        ->orWhere('patient_code', 'LIKE', $term);
                });
            }

            $triageQueue = $query->latest('visit_date')->paginate(10)->appends($request->query());
            $pendingVitalsCount = MedicalRecord::whereNull('bp_systolic')->orWhere('bp_systolic', '')->count();
            $todayVisitsCount = MedicalRecord::whereDate('visit_date', today())->count();

            $nurseuser = auth()->user();
            $nurseuser->load('department');
            $doctoruser = User::role('doctor')->first();
            $assignedDocName = $doctoruser ? $doctoruser->full_name : 'Dr. Julian Vance';
            if (!str_starts_with($assignedDocName, 'Dr.')) {
                $assignedDocName = 'Dr. ' . $assignedDocName;
            }

            $nurseShift = [
                'name' => $nurseuser ? $nurseuser->full_name : ($user->name ?? 'Nurse Workspace'),
                'department' => $nurseuser->department->department_name ?? 'Emergency & Outpatient Triage',
                'shift' => 'Morning Shift (07:00 AM - 05:00 PM)',
                'assigned_doctor' => $assignedDocName,
            ];

            return view('form.doctor.index', compact(
                'userRole',
                'triageQueue',
                'pendingVitalsCount',
                'todayVisitsCount',
                'nurseShift'
            ));
        }

        // ------------------------------------------------------------------
        // 3. DOCTOR ROLE: Primary Interactive Consultation Workspace
        // ------------------------------------------------------------------
        $doctoruser = auth()->user();
        $doctoruser->load('department');

        $activeRecord = null;
        if ($request->filled('record_id')) {
            $activeRecord = MedicalRecord::with(['patient', 'doctor'])->find($request->record_id);
        }

        if (!$activeRecord) {
            $activeRecord = MedicalRecord::with(['patient', 'doctor'])
                ->waiting()
                ->oldest('visit_date')
                ->first();
        }

        $activeRecord?->load('prescription.items.medicine');

        $historyRecords = $activeRecord
            ? MedicalRecord::where('patient_id', $activeRecord->patient_id)
                ->where('record_id', '!=', $activeRecord->record_id)
                ->latest()
                ->take(5)
                ->get()
            : collect();

        $patientQueue = MedicalRecord::with('patient')
            ->waiting()
            ->oldest('visit_date')
            ->take(10)
            ->get();
        $labTests = LabTest::orderBy('test_name', 'asc')->get();
        $availableRooms = Room::where('status', 'available')->orderBy('room_number', 'asc')->get();

        $docName = $doctoruser->full_name ?? $doctoruser->name ?? 'Dr. Julian Vance';

        if (!str_starts_with($docName, 'Dr.')) {
            $docName = 'Dr. ' . $docName;
        }

        $doctorInfo = [
            'name' => $docName,
            'department' => $doctoruser->department->department_name
                ?? 'Emergency Dept. / ផ្នែកសង្គ្រោះបន្ទាន់',
            'code' => $doctoruser->user_code
                ?? ('DOC-' . str_pad($doctoruser->id, 3, '0', STR_PAD_LEFT)),
        ];
        return view('form.doctor.index', compact(
            'userRole',
            'activeRecord',
            'historyRecords',
            'patientQueue',
            'labTests',
            'availableRooms',
            'doctorInfo'
        ));
    }

    /**
     * Edit specific consultation record
     */
    public function edit($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor'])->findOrFail($id);
        return redirect()->route('doctor.index', ['record_id' => $record->record_id]);
    }

    /**
     * Confirm & Prescribe (Commit Medical Record & Prescription)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'diagnosis' => 'required|string',
            'status_destination' => 'required|in:admit,pharmacy,done',
            'items' => 'nullable|array',
            'items.*.medicine_id' => 'required|distinct|exists:medicines,medicine_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.dosage' => 'required|string|max:255',
            'items.*.frequency' => 'required|string|max:255',
            'items.*.duration_days' => 'required|integer|min:1',
        ]);

        try {
            $record = MedicalRecord::findOrFail($id);
            $doctoruser = auth()->user();
            $empId = $doctoruser ? $doctoruser->id : auth()->id();

            $updateData = [
                'user_id' => $empId,
                'diagnosis' => $request->diagnosis,
                'notes' => $request->notes ?? $record->notes,
                'prescription_notes' => $request->prescription_notes,
                'status_destination' => $request->status_destination,
                'heart_rate' => $request->heart_rate ?? $record->heart_rate,
                'temperature' => $request->temperature ?? $record->temperature,
                'spo2' => $request->spo2 ?? $record->spo2,
            ];

            if ($request->filled('blood_pressure')) {
                $bpParts = explode('/', $request->blood_pressure);
                if (count($bpParts) === 2) {
                    $updateData['bp_systolic'] = (int) trim($bpParts[0]);
                    $updateData['bp_diastolic'] = (int) trim($bpParts[1]);
                }
            }

            DB::transaction(function () use ($record, $updateData, $request) {
                $record->update($updateData);
                $this->syncPrescription($record, $request->input('items', []));
            });

            $message = 'កត់ត្រាការព្យាបាល និងចេញវេជ្ជបញ្ជាជោគជ័យ!';
            if ($request->status_destination == 'admit') {
                $message = 'បានរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅបន្ទប់សម្រាកព្យាបាល (Admit)!';
            } elseif ($request->status_destination == 'pharmacy') {
                $message = 'បានរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅឱសថស្ថាន (Pharmacy)!';
            }

            if ($request->status_destination === 'admit') {
                return redirect()->route('doctor.index', ['record_id' => $record->record_id])
                    ->with('success', $message);
            }

            return redirect()->route('doctor.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Replace the prescription lines of this record. NO stock is touched here.
     */
    protected function syncPrescription(MedicalRecord $record, array $items): void
    {
        $prescription = Prescription::where('record_id', $record->record_id)->first();

        if (empty($items)) {
            if ($prescription) {
                $prescription->items()->delete();
                $prescription->delete();
            }
            return;
        }

        if (!$prescription) {
            $prescription = Prescription::create([
                'record_id' => $record->record_id,
                'prescribed_date' => now(),
            ]);
        }

        $prescription->items()->delete();

        foreach ($items as $row) {
            $prescription->items()->create([
                'medicine_id' => $row['medicine_id'],
                'quantity' => $row['quantity'],
                'dosage' => $row['dosage'],
                'frequency' => $row['frequency'],
                'duration_days' => $row['duration_days'],
            ]);
        }
    }

    /**
     * Medicine list for the doctor's prescription dropdown
     */
    public function searchMedicines(Request $request)
    {
        $term = trim($request->get('q', ''));

        $medicines = Medicine::where('is_active', true)
            ->when($term !== '', fn($q) => $q->where('medicine_name', 'like', "%{$term}%"))
            ->select('medicine_id', 'medicine_name', 'strength', 'unit')
            ->selectSub(
                MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id'),
                'stock_total'
            )
            ->having('stock_total', '>', 0)
            ->orderBy('medicine_name')
            ->limit(300)
            ->get();

        return response()->json($medicines);
    }




    /**
     * Store Referral to Lab Test
     */
    public function storeLabOrder(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:medical_records,record_id',
            'test_ids' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $order = LabOrder::create([
                'record_id' => $request->record_id,
                'order_date' => now(),
                'status' => 'pending',
            ]);

            foreach ($request->test_ids as $testId) {
                $test = LabTest::find($testId);
                LabResult::create([
                    'lab_order_id' => $order->lab_order_id,
                    'test_id' => $testId,
                    'result_value' => 'Pending',
                    'normal_range' => $test ? $test->normal_range : null,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'បានបញ្ជូនទៅពិនិត្យមន្ទីរពិសោធន៍ (Refer to Lab Test) ដោយជោគជ័យ!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការបញ្ជូនទៅ Lab៖ ' . $e->getMessage());
        }
    }

    /**
     * Admit Patient as Inpatient
     */
    public function storeAdmission(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'room_id' => 'required|exists:rooms,room_id',
        ]);

        DB::beginTransaction();
        try {
            // lock the room row so two doctors can't take the same room
            $room = Room::where('room_id', $request->room_id)->lockForUpdate()->firstOrFail();

            if ($room->status !== 'available') {
                DB::rollBack();
                return redirect()->back()->with('error', 'បន្ទប់នេះមិនទំនេរទេ សូមជ្រើសរើសបន្ទប់ផ្សេង។');
            }

            if (Admission::where('patient_id', $request->patient_id)->where('status', 'admitted')->exists()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'អ្នកជំងឺនេះកំពុងសម្រាកព្យាបាលរួចហើយ។');
            }

            $admission = new Admission([
                'patient_id' => $request->patient_id,
                'room_id' => $request->room_id,
                'admission_date' => now(),
                'status' => 'admitted',
            ]);
            $admission->admission_number = $this->generateAdmissionNumber();
            $admission->save();

            $room->update(['status' => 'occupied']);

            DB::commit();
            return redirect()->back()->with('success', 'បានបញ្ចូលអ្នកជំងឺឱ្យសម្រាកព្យាបាល (Admitted as Inpatient) រួចរាល់!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការបញ្ចូលសម្រាកព្យាបាល៖ ' . $e->getMessage());
        }
    }

    protected function generateAdmissionNumber(): string
    {
        $prefix = 'ADM-' . now()->format('Ymd') . '-';
        $count = Admission::where('admission_number', 'like', $prefix . '%')->count();

        do {
            $count++;
            $number = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT); // ADM-20260921-001
        } while (Admission::where('admission_number', $number)->exists());

        return $number;
    }

    /**
     * Update Patient Vitals (Nurse / Doctor)
     */
    public function updateVitals(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:medical_records,record_id',
            'blood_pressure' => 'nullable|string|max:50',
            'heart_rate' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'spo2' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
        ]);

        $record = MedicalRecord::findOrFail($request->record_id);

        $updateData = [
            'heart_rate' => $request->heart_rate ?? $record->heart_rate,
            'temperature' => $request->temperature ?? $record->temperature,
            'spo2' => $request->spo2 ?? $record->spo2,
            'weight' => $request->weight ?? $record->weight,
        ];

        if ($request->filled('blood_pressure')) {
            $bpParts = explode('/', $request->blood_pressure);
            if (count($bpParts) === 2) {
                $updateData['bp_systolic'] = (int) trim($bpParts[0]);
                $updateData['bp_diastolic'] = (int) trim($bpParts[1]);
            }
        }

        $record->update($updateData);

        return redirect()->back()->with('success', 'សញ្ញាជីវិត (Vital Signs) ត្រូវបានធ្វើបច្ចុប្បន្នភាពរួចរាល់!');
    }
}
