<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor']);
    }

    public function index()
    {
        $waitingPatients = MedicalRecord::with(['patient', 'doctor'])
            ->latest('visit_date')
            ->paginate(10);

        return view('form.doctor.index', compact('waitingPatients'));
    }

    public function edit($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor'])->findOrFail($id);

        $historyRecords = MedicalRecord::where('patient_id', $record->patient_id)
            ->where('record_id', '!=', $id)
            ->latest()
            ->get();

        return view('form.doctor.consultation', compact('record', 'historyRecords'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'diagnosis'          => 'required|string',
            'status_destination' => 'required|in:admit,pharmacy,done',
        ]);

        try {
            $record = MedicalRecord::findOrFail($id);

            $record->update([
                'employee_id'        => auth()->id(),
                'diagnosis'          => $request->diagnosis,
                'notes'              => $request->notes,
                'prescription_notes' => $request->prescription_notes,
                'status_destination' => $request->status_destination,
            ]);

            $message = 'កត់ត្រាការព្យាបាលជោគជ័យ!';
            if ($request->status_destination == 'admit') {
                $message = 'បានបញ្ជូនអ្នកជំងឺទៅបន្ទប់សម្រាកព្យាបាល (Admit) ជោគជ័យ!';
            } elseif ($request->status_destination == 'pharmacy') {
                $message = 'បានបញ្ជូនអ្នកជំងឺទៅកាន់បន្ទប់ចេញថ្នាំ (Pharmacy) ជោគជ័យ!';
            } else {
                $message = 'ការពិនិត្យត្រូវបានបញ្ចប់ដោយជោគជ័យ!';
            }

            return redirect()->route('doctor.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហា៖ ' . $e->getMessage())->withInput();
        }
    }
}
