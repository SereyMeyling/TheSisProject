<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\MedicineStockMovement;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Notifications\PrescriptionNotification;
use App\Helpers\NotifiesRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\PrescriptionItemDispense;
use App\Services\Billing\PatientChargeService;

class PrescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin|pharmacist|doctor']);
    }

    /**
     * Display a listing of prescriptions.
     */
    public function index(Request $request)
    {

        $query = Prescription::with(['medicalRecord.patient', 'medicalRecord.doctor', 'items.medicine', 'items.dispenses']);
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('medicalRecord.patient', function ($pq) use ($searchTerm) {
                    $pq->where('full_name', 'LIKE', $searchTerm)
                        ->orWhere('patient_code', 'LIKE', $searchTerm);
                })
                    ->orWhereHas('items.medicine', function ($mq) use ($searchTerm) {
                        $mq->where('medicine_name', 'LIKE', $searchTerm);
                    })
                    ->orWhere('prescription_id', 'LIKE', $searchTerm);
            });
        }

        $prescriptions = $query->orderBy('prescription_id', 'desc')->paginate(10)->appends($request->query());
        $totalPrescriptions = Prescription::count();
        $medicalRecords = MedicalRecord::with(['patient', 'doctor'])->orderBy('record_id', 'desc')->limit(50)->get();
        $medicines = Medicine::where('is_active', true)->orderBy('medicine_name', 'asc')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('form.phamacy.partials.prescription_table', compact('prescriptions'))->render(),
                'total' => $totalPrescriptions,
            ]);
        }

        return view('form.phamacy.prescriptions', compact('prescriptions', 'totalPrescriptions', 'medicalRecords', 'medicines'));
    }


    /**
     * Store a newly created prescription in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:medical_records,record_id',
            'prescribed_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,medicine_id',
            'items.*.dosage' => 'required|string|max:100',
            'items.*.frequency' => 'required|string|max:50',
            'items.*.duration_days' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $prescription = Prescription::create([
                'record_id' => $request->record_id,
                'prescribed_date' => $request->prescribed_date,
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->prescription_id,
                    'medicine_id' => $item['medicine_id'],
                    'dosage' => $item['dosage'],
                    'frequency' => $item['frequency'],
                    'duration_days' => $item['duration_days'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            // Notify pharmacists (need to dispense) + admins
            $recipients = NotifiesRoles::usersForRoles(['admin', 'pharmacist']);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PrescriptionNotification($prescription));
            }

            return redirect()->back()->with(['success' => 'វេជ្ជបញ្ជាត្រូវបានបង្កើតដោយជោគជ័យ (Prescription created successfully)']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with(['error' => 'មានបញ្ហាក្នុងការបង្កើតវេជ្ជបញ្ជា៖ ' . $e->getMessage()]);
        }
    }

    /**
     * Dispense a prescription and deduct batch stock.
     */
    /**
     * Dispense (fully or partially) a prescription, deduct batch stock,
     * and create one pending patient charge per dispensed item.
     * Expects items[<prescription_item_id>] = quantity to dispense now.
     */
    public function dispense(Request $request, $id)
    {
        $requested = [];
        foreach ((array) $request->input('items', []) as $itemId => $qty) {
            if ($qty === null || $qty === '') {
                continue;
            }
            if (!ctype_digit((string) $qty)) {
                return redirect()->back()->with([
                    'error' => 'ចំនួនត្រូវតែជាលេខគត់វិជ្ជមាន',
                ]);
            }
            if ((int) $qty > 0) {
                $requested[(int) $itemId] = (int) $qty;
            }
        }

        if (empty($requested)) {
            return redirect()->back()->with([
                'error' => 'សូមបញ្ចូលចំនួនថ្នាំដែលត្រូវចេញយ៉ាងហោចណាស់មួយមុខ',
            ]);
        }

        $chargeService = app(PatientChargeService::class);

        DB::beginTransaction();

        try {
            // Lock the prescription so two dispenses cannot run at the same time.
            $prescription = Prescription::with(['items.medicine', 'items.dispenses', 'medicalRecord'])
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$prescription) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'រកមិនឃើញវេជ្ជបញ្ជាទេ']);
            }

            if ($prescription->status === 'dispensed') {
                DB::rollBack();
                return redirect()->back()->with([
                    'error' => 'វេជ្ជបញ្ជានេះបានចេញថ្នាំរួចហើយ',
                ]);
            }

            $record = $prescription->medicalRecord;
            if (!$record) {
                throw new \Exception('រកមិនឃើញកំណត់ត្រាវេជ្ជសាស្ត្រនៃវេជ្ជបញ្ជានេះទេ');
            }

            $items = $prescription->items->keyBy('prescription_item_id');

            foreach ($requested as $itemId => $qty) {
                if (!$items->has($itemId)) {
                    throw new \Exception('ថ្នាំមួយមុខមិនមែនជារបស់វេជ្ជបញ្ជានេះទេ');
                }
            }

            foreach ($requested as $itemId => $qty) {
                $item = $items[$itemId];
                $medicine = $item->medicine;
                $medicineName = $medicine->medicine_name ?? 'Unknown';

                if ($qty > $item->remaining_to_dispense) {
                    throw new \Exception(
                        'ថ្នាំ ' . $medicineName . ' នៅសល់ត្រូវចេញត្រឹម ' . $item->remaining_to_dispense . ' ប៉ុណ្ណោះ'
                    );
                }

                if (!$medicine || (float) $medicine->selling_price <= 0) {
                    throw new \Exception(
                        'ថ្នាំ ' . $medicineName . ' មិនទាន់មានតម្លៃលក់ សូមកំណត់តម្លៃសិន'
                    );
                }

                // Deduct stock, oldest expiry first. remaining_quantity is the source of truth.
                $qtyNeeded = $qty;

                $batches = MedicineBatch::where('medicine_id', $item->medicine_id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($qtyNeeded <= 0) {
                        break;
                    }

                    $available = (int) $batch->remaining_quantity;
                    $deduct = min($qtyNeeded, $available);
                    $newQuantity = $available - $deduct;

                    $batch->remaining_quantity = $newQuantity;
                    $batch->quantity_remaining = $newQuantity;
                    $batch->save();

                    MedicineStockMovement::create([
                        'batch_id' => $batch->batch_id,
                        'movement_type' => 'OUT',
                        'quantity' => $deduct,
                        'reference_type' => 'PRESCRIPTION',
                        'reference_id' => $prescription->prescription_id,
                        'movement_date' => now(),
                    ]);

                    $qtyNeeded -= $deduct;
                }

                if ($qtyNeeded > 0) {
                    throw new \Exception(
                        'ថ្នាំ ' . $medicineName . ' មានស្តុកមិនគ្រប់គ្រាន់។ ខ្វះចំនួន ' . $qtyNeeded
                    );
                }

                // Record this dispense event, then bill exactly what was dispensed.
                $dispense = PrescriptionItemDispense::create([
                    'prescription_item_id' => $item->prescription_item_id,
                    'quantity' => $qty,
                    'dispensed_by' => auth()->id(),
                    'dispensed_at' => now(),
                ]);

                $chargeService->create([
                    'patient_id' => $record->patient_id,
                    'medical_record_id' => $record->record_id,
                    'source_type' => 'medicine',
                    'source_id' => $dispense->dispense_id,
                    'source_key' => PatientChargeService::medicineKey($dispense->dispense_id),
                    'description' => trim($medicineName . ' ' . ($medicine->strength ?? '')),
                    'quantity' => $qty,
                    'unit_price' => $medicine->selling_price,
                    'charge_date' => now()->toDateString(),
                ]);
            }

            // Fully dispensed only when every item has been given in full.
            $prescription->load('items.dispenses');
            $allDone = $prescription->items->every(function ($it) {
                return $it->remaining_to_dispense === 0;
            });

            $prescription->status = $allDone ? 'dispensed' : 'partial';
            $prescription->dispensed_at = now();
            $prescription->dispensed_by = auth()->id();
            $prescription->save();

            DB::commit();

            return redirect()->back()->with([
                'success' => $allDone
                    ? 'បានចេញថ្នាំតាមវេជ្ជបញ្ជាគ្រប់ចំនួនដោយជោគជ័យ'
                    : 'បានចេញថ្នាំមួយផ្នែក វេជ្ជបញ្ជានៅមានថ្នាំត្រូវចេញបន្ត',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with([
                'error' => 'មានបញ្ហាក្នុងការចេញថ្នាំ៖ ' . $e->getMessage(),
            ]);
        }
    }
}
