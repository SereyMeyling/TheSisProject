<?php

namespace App\Http\Controllers\MedicalRecord;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
// use App\Models\user;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor|nurse|cashier']);
    }

    public function index()
    {
        $records = MedicalRecord::with(['patient', 'doctor'])
            ->latest('visit_date')
            ->paginate(10);

        $patients = Patient::orderBy('patient_id', 'desc')->get();

        return view('form.medical_records.index', compact('records', 'patients'));
    }

    public function create(Request $request)
    {
        return redirect()->route('medical-records.index', [
            'create' => 1,
            'patient_id' => $request->query('patient_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'visit_date' => 'required|date',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'bp_systolic' => 'nullable|numeric',
            'bp_diastolic' => 'nullable|numeric',
            'heart_rate' => 'nullable|numeric',
            'respiratory_rate' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'spo2' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
        ]);

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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'មានបញ្ហា៖ ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())->withInput();
        }
    }
    public function show($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor'])->findOrFail($id);
        return view('form.medical_records.show', compact('record'));
    }

    public function edit($id)
    {
        $medicalRecord = MedicalRecord::findOrFail($id);
        $patients = Patient::all();
        $doctors = user::all();

        return view('form.medical_records.edit', compact('medicalRecord', 'patients', 'doctors'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'bp_systolic' => 'nullable|numeric',
            'bp_diastolic' => 'nullable|numeric',
            'heart_rate' => 'nullable|numeric',
            'respiratory_rate' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'spo2' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
        ]);

        try {
            $record = MedicalRecord::findOrFail($id);
            $record->update($data);
            $record->load(['patient', 'doctor']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'កែប្រែ Medical Record បានជោគជ័យ!',
                    'row' => view('form.medical_records._row', compact('record'))->render(),
                ]);
            }

            return redirect()->route('medical-records.index')
                ->with('success', 'កែប្រែ Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'មានបញ្ហា៖ ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $record = MedicalRecord::findOrFail($id);
            $record->delete();

            return redirect()->route('medical-records.index')
                ->with('success', 'លុប Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage());
        }
    }
}
