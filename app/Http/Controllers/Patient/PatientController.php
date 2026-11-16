<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor|nurse|cashier']);
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $date = $request->get('date');
        $gender = $request->get('gender');

        $patients = Patient::when($search, function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        })
            ->when($date, fn($q, $date) => $q->whereDate('created_at', $date))
            ->when($gender, fn($q, $gender) => $q->where('sex', $gender))
            ->latest('patient_id')
            ->paginate(10);

        if ($request->ajax()) {
            return view('form.patient.partials.table', compact('patients'));
        }

        $todayCount = Patient::whereDate('created_at', now()->today())->count();
        $newPatients = $todayCount; // same query, no need to run it twice
        $oldPatients = Patient::whereDate('created_at', '<', now()->today())->count();
        $waitingPatients = $this->todayQueue();
        $waitingCount = $waitingPatients->count();

        return view('form.patient.patient', compact(
            'patients',
            'todayCount',
            'newPatients',
            'oldPatients',
            'waitingCount',
            'waitingPatients'
        ));
    }

    public function create()
    {
        $waitingPatients = $this->todayQueue();

        return view('form.patient.create', compact('waitingPatients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'id_card' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'sex' => 'required|string',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ], [
            'full_name.required' => 'សូមបញ្ចូលឈ្មោះពេញ!',
            'id_card.required' => 'សូមបញ្ចូលលេខអត្តសញ្ញាណប័ណ្ណ!',
            'date_of_birth.required' => 'សូមជ្រើសរើសថ្ងៃខែឆ្នាំកំណើត!',
            'sex.required' => 'សូមជ្រើសរើសភេទ!',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ!',
            'address.required' => 'សូមបញ្ចូលអាសយដ្ឋាន!',
        ]);

        try {
            $maxId = Patient::max('patient_id') ?? 0;
            $nextId = $maxId + 1;
            $patientCode = '#PT-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

            Patient::create([
                'patient_code' => $patientCode,
                'full_name' => $request->full_name,
                'id_card' => $request->id_card,
                'date_of_birth' => $request->date_of_birth,
                'sex' => $request->sex,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            return redirect()->route('patients.index')->with('success', 'ចុះឈ្មោះអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការចុះឈ្មោះ៖ ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $patient = Patient::where('patient_id', $id)->firstOrFail();
        $medicalRecords = MedicalRecord::where('patient_id', $id)->latest('record_id')->get();
        $waitingPatients = $this->todayQueue();

        return view('form.patient.show', compact('patient', 'medicalRecords', 'waitingPatients'));
    }

    public function edit($id)
    {
        $patient = Patient::where('patient_id', $id)->firstOrFail();
        $waitingPatients = $this->todayQueue();

        return view('form.patient.edit', compact('patient', 'waitingPatients'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'id_card' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'sex' => 'required|string',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ], [
            'full_name.required' => 'សូមបញ្ចូលឈ្មោះពេញ!',
            'id_card.required' => 'សូមបញ្ចូលលេខអត្តសញ្ញាណប័ណ្ណ!',
            'date_of_birth.required' => 'សូមជ្រើសរើសថ្ងៃខែឆ្នាំកំណើត!',
            'sex.required' => 'សូមជ្រើសរើសភេទ!',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ!',
            'address.required' => 'សូមបញ្ចូលអាសយដ្ឋាន!',
        ]);

        try {
            $patient = Patient::where('patient_id', $id)->firstOrFail();
            $patient->update([
                'full_name' => $request->full_name,
                'id_card' => $request->id_card,
                'date_of_birth' => $request->date_of_birth,
                'sex' => $request->sex,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            return redirect()->route('patients.index')->with('success', 'កែប្រែព័ត៌មានអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការកែប្រែ៖ ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $patient = Patient::where('patient_id', $id)->firstOrFail();
            $patient->delete();

            return redirect()->route('patients.index')->with('success', 'លុបទិន្នន័យអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'មានបញ្ហាក្នុងការលុប៖ ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $patient = Patient::findOrFail($id);
        return view('form.patient.print', compact('patient'));
    }

    private function todayQueue()
    {
        return MedicalRecord::with('patient')
            ->whereDate('created_at', now()->toDateString())
            ->latest('record_id')
            ->get();
    }
}
