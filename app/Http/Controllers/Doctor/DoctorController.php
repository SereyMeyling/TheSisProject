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
            $activeRecord = MedicalRecord::with(['patient', 'doctor'])->latest('visit_date')->first();
        }

        // Fallback demo record if table is empty
        if (!$activeRecord) {
            $samplePatient = Patient::first();
            if (!$samplePatient) {
                $samplePatient = Patient::firstOrCreate(
                    ['patient_code' => 'ID-2001-0023'],
                    [
                        'full_name' => 'លោក ពាក់ មី',
                        'id_card' => '012345678901',
                        'sex' => 'Male',
                        'date_of_birth' => '2006-05-12',
                        'phone' => '012 345 678',
                        'address' => 'រាជធានីភ្នំពេញ',
                    ]
                );
            }

            $userId = $doctoruser?->id ?? $user->id;

            $activeRecord = MedicalRecord::create([
                'patient_id' => $samplePatient->patient_id,
                'user_id' => $userId,
                'visit_date' => now(),
                'bp_systolic' => 120,
                'bp_diastolic' => 80,
                'heart_rate' => 75,
                'temperature' => 38.6,
                'spo2' => 98,
                'weight' => 65,
                'diagnosis' => 'Acute Pharyngitis',
                'notes' => 'ក្ដៅខ្លួន ឈឺបំពង់ក និងអស់កម្លាំង (Fever, Sore Throat & Fatigue) | អ្នកជំងឺមានអាការៈឈឺបំពង់ក ២ថ្ងៃមកហើយ។',
                'prescription_notes' => 'Paracetamol 500mg (2 tabs x 3 times/day after meal), Amoxicillin 500mg (1 tab x 2 times/day)',
                'status_destination' => 'pharmacy',
            ]);
            $activeRecord->load(['patient', 'doctor']);
        }

        $historyRecords = MedicalRecord::where('patient_id', $activeRecord->patient_id)
            ->where('record_id', '!=', $activeRecord->record_id)
            ->latest()
            ->take(5)
            ->get();

        $patientQueue = MedicalRecord::with('patient')->latest()->take(10)->get();
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
        ]);

        try {
            $record = MedicalRecord::findOrFail($id);
            $doctoruser = auth()->user();
            $doctoruser->load('department');
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

            $record->update($updateData);

            $message = 'កត់ត្រាការព្យាបាល និងចេញវេជ្ជបញ្ជាជោគជ័យ!';
            if ($request->status_destination == 'admit') {
                $message = 'បានរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅបន្ទប់សម្រាកព្យាបាល (Admit)!';
            } elseif ($request->status_destination == 'pharmacy') {
                $message = 'បានរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅឱសថស្ថាន (Pharmacy)!';
            }

            return redirect()->route('doctor.index', ['record_id' => $record->record_id])->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())->withInput();
        }
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
            Admission::create([
                'patient_id' => $request->patient_id,
                'room_id' => $request->room_id,
                'admission_date' => now(),
                'status' => 'admitted',
            ]);

            $room = Room::find($request->room_id);
            if ($room) {
                $room->update(['status' => 'occupied']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'បានបញ្ចូលអ្នកជំងឺឱ្យសម្រាកព្យាបាល (Admitted as Inpatient) រួចរាល់!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការបញ្ចូលសម្រាកព្យាបាល៖ ' . $e->getMessage());
        }
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
