<?php

namespace App\Http\Controllers\MedicalRecord;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Employee;
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

        return view('form.medical_records.index', compact('records'));
    }

    public function create(Request $request)
    {
        $patients = Patient::orderBy('patient_id', 'desc')->get();
        $doctors = Employee::all();
        $selectedPatientId = $request->query('patient_id');

        return view('form.medical_records.create', compact('patients', 'doctors', 'selectedPatientId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
        ]);

        try {
            DB::beginTransaction();

            $employeeId = $request->employee_id ?? Employee::value('employee_id');

            MedicalRecord::create([
                'patient_id'       => $request->patient_id,
                'employee_id'      => $employeeId,
                'visit_date'       => $request->visit_date ?? now(),
                'diagnosis'        => $request->diagnosis,
                'notes'            => $request->notes,
                'bp_systolic'      => $request->bp_systolic,
                'bp_diastolic'     => $request->bp_diastolic,
                'heart_rate'       => $request->heart_rate,
                'respiratory_rate' => $request->respiratory_rate,
                'temperature'      => $request->temperature,
                'spo2'             => $request->spo2,
                'weight'           => $request->weight,
            ]);

            DB::commit();

            return redirect()->route('medical-records.index')
                ->with('success', 'រក្សាទុក Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())
                ->withInput();
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
        $doctors = Employee::all();

        return view('form.medical_records.edit', compact('medicalRecord', 'patients', 'doctors'));
    }

    public function update(Request $request, $id)
    {
        try {
            $record = MedicalRecord::findOrFail($id);
            $record->update($request->all());

            return redirect()->route('medical-records.index')
                ->with('success', 'កែប្រែ Medical Record បានជោគជ័យ!');
        } catch (\Exception $e) {
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
