<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            ->when($gender, fn($q, $gender) => $q->where('sex', strtolower($gender)))
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

    /**
     * Validation rules shared by store and update; lengths match the patients table.
     */
    protected function rules($ignoreId = null): array
    {
        // Limits follow the production patients table: full_name varchar(50),
        // id_card varchar(20) UNIQUE. Longer/duplicate values crashed with a DB error.
        return [
            'full_name' => 'required|string|max:50',
            'id_card' => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('patients', 'id_card')->ignore($ignoreId, 'patient_id'),
            ],
            'date_of_birth' => 'required|date|before_or_equal:today',
            'sex' => 'required|string|in:male,female,other',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'full_name.required' => 'សូមបញ្ចូលឈ្មោះពេញ!',
            'id_card.required' => 'សូមបញ្ចូលលេខអត្តសញ្ញាណប័ណ្ណ!',
            'id_card.max' => 'លេខអត្តសញ្ញាណប័ណ្ណមិនអាចលើសពី 20 តួអក្សរទេ!',
            'id_card.unique' => 'លេខអត្តសញ្ញាណប័ណ្ណនេះមានរួចហើយ! (This ID card is already registered.)',
            'full_name.max' => 'ឈ្មោះពេញមិនអាចលើសពី 50 តួអក្សរទេ! (Name is too long.)',
            'date_of_birth.required' => 'សូមជ្រើសរើសថ្ងៃខែឆ្នាំកំណើត!',
            'date_of_birth.before_or_equal' => 'ថ្ងៃខែឆ្នាំកំណើតមិនអាចនៅថ្ងៃអនាគតបានទេ!',
            'sex.required' => 'សូមជ្រើសរើសភេទ!',
            'sex.in' => 'ភេទមិនត្រឹមត្រូវ!',
            'phone.required' => 'សូមបញ្ចូលលេខទូរស័ព្ទ!',
            'address.required' => 'សូមបញ្ចូលអាសយដ្ឋាន!',
        ];
    }

    /**
     * Store the sex value in the lowercase form the table enum expects.
     */
    protected function normalizeSex(Request $request): void
    {
        if ($request->has('sex')) {
            $request->merge(['sex' => strtolower(trim((string) $request->input('sex')))]);
        }
    }

    public function store(Request $request)
    {
        $this->normalizeSex($request);
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::transaction(function () use ($validated) {
                // Insert with a unique temporary code, then derive the real code from the
                // auto-increment id, so concurrent registrations never get the same code (L2).
                $patient = Patient::create(array_merge($validated, [
                    // Must fit patient_code varchar(20) on the production schema.
                    'patient_code' => 'TMP-' . Str::random(16),
                ]));

                $patient->patient_code = '#PT-' . date('Y') . '-' . str_pad($patient->patient_id, 3, '0', STR_PAD_LEFT);
                $patient->save();
            });

            return redirect()->route('patients.index')->with('success', 'ចុះឈ្មោះអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            Log::error('Patient registration failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'មានបញ្ហាក្នុងការចុះឈ្មោះ សូមព្យាយាមម្តងទៀត។ (Failed to register the patient.)')
                ->withInput();
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
        $this->normalizeSex($request);
        $validated = $request->validate($this->rules($id), $this->messages());

        $patient = Patient::where('patient_id', $id)->firstOrFail();

        try {
            $patient->update($validated);

            return redirect()->route('patients.index')->with('success', 'កែប្រែព័ត៌មានអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            Log::error('Patient update failed', ['patient_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'មានបញ្ហាក្នុងការកែប្រែ សូមព្យាយាមម្តងទៀត។ (Failed to update the patient.)')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $patient = Patient::where('patient_id', $id)->firstOrFail();

        try {
            $patient->delete();

            return redirect()->route('patients.index')->with('success', 'លុបទិន្នន័យអ្នកជំងឺជោគជ័យ!');
        } catch (\Exception $e) {
            // usually an FK error: the patient has records, appointments or invoices
            Log::error('Patient delete failed', ['patient_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with(
                'error',
                'មិនអាចលុបអ្នកជំងឺនេះបានទេ ព្រោះមានទិន្នន័យពាក់ព័ន្ធ។ (This patient has related records and cannot be deleted.)'
            );
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
