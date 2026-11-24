<?php

namespace App\Http\Controllers\MedicalRecord;

use App\Http\Controllers\Controller;
use App\Models\Billing\PatientCharge;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalRecordController extends Controller
{
    // Patients preloaded in the "create" select; the rest are found by search.
    private const PATIENT_LIST_LIMIT = 100;

    private const MSG_ERROR = 'មានបញ្ហា សូមព្យាយាមម្តងទៀត (Something went wrong, please try again)';
    private const MSG_FORBIDDEN = 'អ្នកមិនមានសិទ្ធិកែប្រែ ឬលុប Medical Record នេះទេ (You are not allowed to edit or delete this medical record)';

    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor|nurse|cashier']);
    }

    public function index(Request $request)
    {
        // AJAX patient search for the create form (avoids loading every patient).
        if ($request->filled('patient_q') && $request->expectsJson()) {
            $term = '%' . $request->query('patient_q') . '%';

            $patients = Patient::select('patient_id', 'patient_code', 'full_name')
                ->where(function ($q) use ($term) {
                    $q->where('full_name', 'LIKE', $term)
                        ->orWhere('patient_code', 'LIKE', $term);
                })
                ->orderBy('patient_id', 'desc')
                ->limit(30)
                ->get();

            return response()->json(['patients' => $patients]);
        }

        $records = MedicalRecord::with(['patient', 'doctor'])
            ->latest('visit_date')
            ->paginate(10);

        $patients = Patient::select('patient_id', 'patient_code', 'full_name')
            ->orderBy('patient_id', 'desc')
            ->limit(self::PATIENT_LIST_LIMIT)
            ->get();

        // Opened from a patient page (?create=1&patient_id=...): make sure that patient is in the list.
        $requestedId = (int) $request->query('patient_id');
        if ($requestedId && !$patients->contains('patient_id', $requestedId)) {
            $requested = Patient::select('patient_id', 'patient_code', 'full_name')->find($requestedId);
            if ($requested) {
                $patients->prepend($requested);
            }
        }

        return view('form.medical_records.index', compact('records', 'patients'));
    }

    public function create(Request $request)
    {
        return redirect()->route('medical-records.index', [
            'create' => 1,
            'patient_id' => $request->query('patient_id'),
        ]);
    }

    /**
     * Validation rules shared by store and update (columns: varchar(255), INT, DECIMAL).
     */
    private function vitalRules(): array
    {
        return [
            'diagnosis' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'bp_systolic' => 'nullable|numeric|between:30,300',
            'bp_diastolic' => 'nullable|numeric|between:20,200',
            'heart_rate' => 'nullable|integer|between:20,300',
            'respiratory_rate' => 'nullable|integer|between:4,80',
            'temperature' => 'nullable|numeric|between:30,45',
            'spo2' => 'nullable|numeric|between:0,100',
            'weight' => 'nullable|numeric|between:0,500',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'visit_date' => 'required|date',
        ] + $this->vitalRules());

        try {
            $record = MedicalRecord::create($data + ['user_id' => auth()->id()]);
            $record->load(['patient', 'doctor']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'រក្សាទុក Medical Record បានជោគជ័យ!',
                    'row' => view('form.medical_records._row', compact('record'))->render(),
                ]);
            }

            return redirect()->route('medical-records.index')
                ->with('success', 'រក្សាទុក Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            Log::error('Medical record create failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['message' => self::MSG_ERROR], 500);
            }
            return redirect()->back()->with('error', self::MSG_ERROR)->withInput();
        }
    }

    public function show($id)
    {
        $record = MedicalRecord::with([
            'patient',
            'doctor',
            'prescriptions',
            'labOrders.results.labTest',
        ])->findOrFail($id);

        $canModify = $record->canBeModifiedBy(auth()->user());

        return view('form.medical_records.show', compact('record', 'canModify'));
    }

    public function edit($id)
    {
        $medicalRecord = MedicalRecord::with(['patient', 'doctor'])->findOrFail($id);

        if (!$medicalRecord->canBeModifiedBy(auth()->user())) {
            return redirect()->route('medical-records.show', $medicalRecord->record_id)
                ->with('error', self::MSG_FORBIDDEN);
        }

        return view('form.medical_records.edit', compact('medicalRecord'));
    }

    public function update(Request $request, $id)
    {
        $record = MedicalRecord::findOrFail($id);

        if (!$record->canBeModifiedBy(auth()->user())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => self::MSG_FORBIDDEN], 403);
            }
            return redirect()->route('medical-records.show', $record->record_id)
                ->with('error', self::MSG_FORBIDDEN);
        }

        // patient_id is intentionally not editable: a record never moves to another patient.
        $data = $request->validate($this->vitalRules());

        try {
            $record->update($data);
            $record->load(['patient', 'doctor']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'កែប្រែ Medical Record បានជោគជ័យ!',
                    'row' => view('form.medical_records._row', compact('record'))->render(),
                ]);
            }

            return redirect()->route('medical-records.show', $record->record_id)
                ->with('success', 'កែប្រែ Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            Log::error('Medical record update failed', [
                'record_id' => $record->record_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => self::MSG_ERROR], 500);
            }
            return redirect()->back()->with('error', self::MSG_ERROR)->withInput();
        }
    }

    public function destroy($id)
    {
        $record = MedicalRecord::findOrFail($id);

        if (!$record->canBeModifiedBy(auth()->user())) {
            return redirect()->route('medical-records.show', $record->record_id)
                ->with('error', self::MSG_FORBIDDEN);
        }

        // Records referenced by prescriptions, lab orders or charges must be kept (FK + history).
        $hasChildren = $record->prescriptions()->exists()
            || $record->labOrders()->exists()
            || PatientCharge::where('medical_record_id', $record->record_id)->exists();

        if ($hasChildren) {
            return redirect()->route('medical-records.show', $record->record_id)
                ->with('error', 'មិនអាចលុប Medical Record នេះបានទេ ព្រោះមានវេជ្ជបញ្ជា ការពិនិត្យមន្ទីរពិសោធន៍ ឬការគិតថ្លៃភ្ជាប់ជាមួយ (Cannot delete this record: it has prescriptions, lab orders or charges)');
        }

        try {
            $record->delete();

            return redirect()->route('medical-records.index')
                ->with('success', 'លុប Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            Log::error('Medical record delete failed', [
                'record_id' => $record->record_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', self::MSG_ERROR);
        }
    }
}
