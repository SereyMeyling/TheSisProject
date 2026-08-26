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
        $date   = $request->get('date');
        $gender = $request->get('gender');

        $todayCount   = Patient::whereDate('created_at', now()->today())->count();
        $newPatients  = Patient::whereDate('created_at', now()->today())->count();
        $oldPatients  = Patient::whereDate('created_at', '<', now()->today())->count();
        $waitingCount = MedicalRecord::whereDate('created_at', now()->today())->count();

        $patients = Patient::when($search, function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        })
            ->when($date, function ($query, $date) {
                return $query->whereDate('created_at', $date);
            })
            ->when($gender, function ($query, $gender) {
                return $query->where('sex', $gender);
            })
            ->latest('patient_id')
            ->paginate(10);

        // ៣. Live Waiting Queue
        $waitingPatients = MedicalRecord::with('patient')
            ->latest('record_id')
            ->take(10)
            ->get();

        // កែសម្រួលត្រង់នេះឱ្យទៅចំ file patient.blade.php របស់បង
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
        $waitingPatients = MedicalRecord::with('patient')
            ->latest('record_id')
            ->take(10)
            ->get();

        return view('form.patient.create', compact('waitingPatients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:255',
            'id_card'       => 'nullable|string|max:100',
            'date_of_birth' => 'required|date',
            'sex'           => 'required',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
        ]);

        try {
            $maxId       = Patient::max('patient_id') ?? 0;
            $nextId      = $maxId + 1;
            $patientCode = '#PT-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

            Patient::create([
                'patient_code'  => $patientCode,
                'full_name'     => $request->full_name,
                'id_card'       => $request->id_card,
                'date_of_birth' => $request->date_of_birth,
                'sex'           => $request->sex,
                'phone'         => $request->phone,
                'address'       => $request->address,
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

        $waitingPatients = MedicalRecord::with('patient')
            ->latest('record_id')
            ->take(10)
            ->get();

        return view('form.patient.show', compact('patient', 'medicalRecords', 'waitingPatients'));
    }

    public function edit($id)
    {
        $patient = Patient::where('patient_id', $id)->firstOrFail();

        $waitingPatients = MedicalRecord::with('patient')
            ->latest('record_id')
            ->take(10)
            ->get();

        return view('form.patient.edit', compact('patient', 'waitingPatients'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name'     => 'required|string|max:255',
            'id_card'       => 'nullable|string|max:100',
            'date_of_birth' => 'required|date',
            'sex'           => 'required',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
        ]);

        try {
            $patient = Patient::where('patient_id', $id)->firstOrFail();
            $patient->update([
                'full_name'     => $request->full_name,
                'id_card'       => $request->id_card,
                'date_of_birth' => $request->date_of_birth,
                'sex'           => $request->sex,
                'phone'         => $request->phone,
                'address'       => $request->address,
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
}
