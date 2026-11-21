<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\Admission;
use App\Models\Setting\GeneralSettings;
use App\Models\Setting\InvoiceSetting;
use App\Notifications\PaymentNotification;
use App\Helpers\NotifiesRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Services\Billing\PatientChargeService;
use App\Services\Billing\RoomChargeService;
use Illuminate\Support\Facades\Log;
use App\Models\Billing\PatientCharge;

class BillingController extends Controller
{
    /**
     * Middleware check: Only Admin or Cashier (or process-payments / view-invoices) can access.
     */
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if (!$user || (!$user->hasAnyRole(['admin', 'cashier']) && !$user->hasAnyPermission(['view-invoices', 'process-payments']))) {
                abort(403, 'អ្នកមិនមានសិទ្ធិចូលប្រើប្រាស់ទំព័រ Billing ទេ។ (You do not have permission to access the Billing module.)');
            }
            return $next($request);
        });
    }

    /**
     * Authorization helper for edit/cancel actions.
     * Admins always pass; otherwise the specific Spatie permission is required.
     * (Add 'edit-invoices' / 'cancel-invoices' permissions via the Role &
     * Permission screen — they are not created automatically here.)
     */
    private function authorizeAction(string $permission): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasPermissionTo($permission))) {
            abort(403, 'អ្នកមិនមានសិទ្ធិធ្វើសកម្មភាពនេះទេ (You do not have permission to perform this action).');
        }
    }

    /**
     * Display a listing of invoices with search, filters, and summary stats.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['items', 'patient', 'payments', 'admission.room']);
        $billing = InvoiceSetting::first();

        // Search filter: invoice_number, patient_name, patient_phone
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'LIKE', $searchTerm)
                    ->orWhere('patient_name', 'LIKE', $searchTerm)
                    ->orWhere('patient_phone', 'LIKE', $searchTerm);
            });
        }

        // Status filter: unpaid, paid, partial, cancelled
        if ($request->filled('status') && in_array($request->status, ['paid', 'unpaid', 'partial', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Visit type filter: opd / ipd
        if ($request->filled('visit_type')) {
            if ($request->visit_type === 'opd') {
                $query->whereNull('admission_id');
            } elseif ($request->visit_type === 'ipd') {
                $query->whereNotNull('admission_id');
            }
        }

        // From date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // To date
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

        // Stats summary
        $totalInvoices = Invoice::count();
        $todayCashRevenue = InvoicePayment::whereDate('paid_at', today())
            ->where('payment_method', 'cash')
            ->sum('amount');

        $todayKhqrRevenue = InvoicePayment::whereDate('paid_at', today())
            ->where('payment_method', 'khqr')
            ->sum('amount');
        $totalUnpaid = Invoice::where('status', '!=', 'cancelled')->sum('balance');

        $patients = Patient::select(['patient_id', 'full_name', 'phone', 'patient_code'])->get();

        // Active (currently admitted) admissions, for the IPD invoice picker.
        $admissions = Admission::with(['patient', 'room'])
            ->where('status', 'admitted')
            ->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('form.billing.partials.table', compact('invoices'))->render(),
                'totalInvoices' => $totalInvoices,
                'todayCashRevenue' => number_format($todayCashRevenue, 2),
                'todayKhqrRevenue' => number_format($todayKhqrRevenue, 2),
                'totalUnpaid' => number_format($totalUnpaid, 2),
            ]);
        }

        return view('form.billing.index', compact('invoices', 'totalInvoices', 'todayCashRevenue', 'todayKhqrRevenue', 'totalUnpaid', 'patients', 'admissions', 'billing'));
    }


    /**
     * Pending charges for one patient, for the "create invoice" form.
     * Also tops up missing daily room charges (idempotent).
     */
    public function pendingCharges($patient): JsonResponse
    {
        $patientModel = Patient::select(['patient_id', 'patient_code', 'full_name', 'phone'])->find($patient);

        if (!$patientModel) {
            return response()->json([
                'success' => false,
                'message' => 'រកមិនឃើញអ្នកជំងឺ (Patient not found).',
            ], 404);
        }

        $warnings = [];

        try {
            app(RoomChargeService::class)->accrueForPatient($patientModel->patient_id);
        } catch (\Throwable $e) {
            Log::error('Room charge top-up failed', [
                'patient_id' => $patientModel->patient_id,
                'error' => $e->getMessage(),
            ]);
            $warnings[] = 'មិនអាចធ្វើបច្ចុប្បន្នភាពថ្លៃបន្ទប់បានទេ៖ ' . $e->getMessage();
        }

        $charges = app(PatientChargeService::class)->pendingForPatient($patientModel->patient_id);

        $items = $charges->map(function ($charge) {
            return [
                'charge_id' => $charge->id,
                'source_type' => $charge->source_type,
                'item_type' => $charge->invoiceItemType(),
                'description' => $charge->description,
                'quantity' => (int) $charge->quantity,
                'unit_price' => (float) $charge->unit_price,
                'total_price' => (float) $charge->total_price,
                'charge_date' => $charge->charge_date ? $charge->charge_date->toDateString() : null,
                'status' => $charge->status,
            ];
        })->values();

        $admission = Admission::where('patient_id', $patientModel->patient_id)
            ->where('status', 'admitted')
            ->first(['admission_id']);

        return response()->json([
            'success' => true,
            'patient' => [
                'patient_id' => $patientModel->patient_id,
                'patient_code' => $patientModel->patient_code,
                'full_name' => $patientModel->full_name,
                'phone' => $patientModel->phone,
            ],
            'admission_id' => $admission ? $admission->admission_id : null,
            'items' => $items,
            'total' => round((float) $items->sum('total_price'), 2),
            'warnings' => $warnings,
            'message' => $items->isEmpty()
                ? 'គ្មានការគិតលុយដែលមិនទាន់បង់សម្រាប់អ្នកជំងឺនេះទេ'
                : null,
        ]);
    }

    /**
     * Show detailed, read-only invoice info (View Detail modal + printable receipt).
     */
    public function show($id)
    {
        $invoice = Invoice::with(['items', 'patient', 'payments.processor', 'creator', 'canceller', 'admission.room'])
            ->findOrFail($id);

        $setting = GeneralSettings::first();
        $billing = InvoiceSetting::first();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $invoice,
                'html' => view('form.billing.partials.receipt', compact('invoice', 'setting', 'billing'))->render(),
            ]);
        }

        return redirect()->route('billing.index', ['view' => $invoice->id]);
    }

    // Edit an existing invoice (only allowed while unpaid and nothing has been paid against it yet).

    public function edit($id): JsonResponse
    {
        $this->authorizeAction('edit-invoices');

        $invoice = Invoice::with(['items'])->withCount('charges')->findOrFail($id);

        if (!$invoice->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'វិក្កយបត្រនេះលែងអាចកែប្រែបានទៀតហើយ (This invoice can no longer be edited).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Create a new invoice from pending patient charges and/or manual rows.
     * Charge prices always come from the database, never from the browser.
     */
    public function store(StoreInvoiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $chargeIds = collect($request->input('charge_ids', []))
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values();

            $manualItems = $request->input('items', []);
            $charges = collect();

            if ($chargeIds->isNotEmpty()) {
                if (!$request->filled('patient_id')) {
                    throw new \DomainException('សូមជ្រើសរើសអ្នកជំងឺ មុននឹងបញ្ចូលការគិតលុយដែលមានស្រាប់។');
                }

                // Lock the rows so a second cashier cannot bill them at the same moment.
                $charges = PatientCharge::whereIn('id', $chargeIds)
                    ->orderBy('charge_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($charges->count() !== $chargeIds->count()) {
                    throw new \DomainException('មានការគិតលុយមួយចំនួនរកមិនឃើញ សូមជ្រើសរើសអ្នកជំងឺម្តងទៀត។');
                }

                foreach ($charges as $charge) {
                    if ((int) $charge->patient_id !== (int) $request->patient_id) {
                        throw new \DomainException('ការគិតលុយមួយមិនមែនជារបស់អ្នកជំងឺនេះទេ។');
                    }
                    if (!$charge->isPending()) {
                        throw new \DomainException(
                            'ការគិតលុយ "' . $charge->description . '" ត្រូវបានដាក់ក្នុងវិក្កយបត្រ ឬលុបចោលរួចហើយ។ សូមជ្រើសរើសអ្នកជំងឺម្តងទៀត។'
                        );
                    }
                }
            }

            if ($charges->isEmpty() && empty($manualItems)) {
                throw new \DomainException('សូមបញ្ចូលយ៉ាងហោចណាស់ធាតុទូទាត់មួយ។');
            }

            // The server decides IPD vs OPD when room charges are included.
            $admissionId = $request->admission_id;
            $roomAdmissionIds = $charges->where('source_type', PatientCharge::TYPE_ROOM)
                ->pluck('admission_id')
                ->filter()
                ->unique()
                ->values();

            if ($roomAdmissionIds->count() > 1) {
                throw new \DomainException('មិនអាចដាក់ថ្លៃបន្ទប់ពីការចូលសម្រាកផ្សេងគ្នាក្នុងវិក្កយបត្រតែមួយបានទេ។');
            }
            if ($roomAdmissionIds->count() === 1) {
                if ($admissionId && (int) $admissionId !== (int) $roomAdmissionIds->first()) {
                    throw new \DomainException('ថ្លៃបន្ទប់មិនត្រូវនឹងការចូលសម្រាកដែលបានជ្រើសរើសទេ។');
                }
                $admissionId = $roomAdmissionIds->first();
            }

            // Total = charges (from the database) + manual rows.
            $totalAmount = (float) $charges->sum('total_price');
            foreach ($manualItems as $item) {
                $totalAmount += $item['qty'] * $item['unit_price'];
            }
            $totalAmount = round($totalAmount, 2);

            $invoiceNumber = $this->generateInvoiceNumber();

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'patient_id' => $request->patient_id,
                'admission_id' => $admissionId, // null = OPD, set = IPD
                'patient_name' => $request->patient_name,
                'patient_phone' => $request->patient_phone,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'status' => 'unpaid',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($charges as $charge) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $charge->invoiceItemType(),
                    'description' => $charge->description,
                    'qty' => $charge->quantity,
                    'unit_price' => $charge->unit_price,
                    'subtotal' => $charge->total_price,
                ]);
            }

            foreach ($manualItems as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item['item_type'],
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }

            if ($charges->isNotEmpty()) {
                // Defence in depth: only still-pending rows may be linked.
                $linked = PatientCharge::whereIn('id', $chargeIds)
                    ->where('status', PatientCharge::STATUS_PENDING)
                    ->update([
                        'status' => PatientCharge::STATUS_INVOICED,
                        'invoice_id' => $invoice->id,
                        'invoiced_at' => now(),
                    ]);

                if ($linked !== $charges->count()) {
                    throw new \Exception('Failed to link every charge to the invoice.');
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'វិក្កយបត្រត្រូវបានបង្កើតដោយជោគជ័យ (Invoice created successfully)',
                    'data' => $invoice->load(['items', 'patient', 'admission.room']),
                ], 201);
            }

            return redirect()->route('billing.index')->with('success', 'Invoice created successfully.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update an existing invoice (only allowed while unpaid and nothing has been paid against it yet).

    public function update(UpdateInvoiceRequest $request, $id)
    {
        $this->authorizeAction('edit-invoices');

        DB::beginTransaction();
        try {
            $invoice = Invoice::where('id', $id)->lockForUpdate()->firstOrFail();

            // Charge-backed invoices keep their items (prices come from patient_charges).
            // Only the descriptive fields can change.
            if ($invoice->charges()->exists()) {
                $invoice->update([
                    'patient_name' => $request->patient_name,
                    'patient_phone' => $request->patient_phone,
                    'notes' => $request->notes,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'វិក្កយបត្រត្រូវបានកែប្រែដោយជោគជ័យ (Invoice updated successfully)',
                    'data' => $invoice->fresh(['items', 'patient', 'admission.room']),
                ]);
            }

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['qty'] * $item['unit_price'];
            }

            $invoice->update([
                'patient_name' => $request->patient_name,
                'patient_phone' => $request->patient_phone,
                'notes' => $request->notes,
                'total_amount' => $totalAmount,
                'balance' => $totalAmount, // paid_amount is guaranteed 0 here
            ]);

            // Replace items wholesale — simplest and safest for an unpaid,
            // not-yet-touched invoice. Avoids diffing add/remove/update.
            $invoice->items()->delete();
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item['item_type'],
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'វិក្កយបត្រត្រូវបានកែប្រែដោយជោគជ័យ (Invoice updated successfully)',
                'data' => $invoice->fresh(['items', 'patient', 'admission.room']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an invoice (soft, audit-preserving — never delete).
     * Only allowed while unpaid and nothing has been paid against it yet.
     */
    public function cancel(CancelInvoiceRequest $request, $id)
    {
        $this->authorizeAction('cancel-invoices');

        DB::beginTransaction();
        try {
            $invoice = Invoice::where('id', $id)->lockForUpdate()->firstOrFail();

            if (!$invoice->isCancellable()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'វិក្កយបត្រនេះមិនអាចលុបចោលបានទេ (This invoice cannot be cancelled — it is already paid, partially paid, or already cancelled).',
                ], 422);
            }
            // Release this invoice's charges so the cashier can bill them again.
            foreach ($invoice->charges()->lockForUpdate()->get() as $charge) {
                $charge->update([
                    'status' => PatientCharge::STATUS_PENDING,
                    'invoice_id' => null,
                    'invoiced_at' => null,
                    'notes' => trim(($charge->notes ? $charge->notes . "\n" : '')
                        . 'Released from cancelled invoice ' . $invoice->invoice_number . ' on ' . now()->toDateTimeString()),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'វិក្កយបត្រត្រូវបានលុបចោលដោយជោគជ័យ (Invoice cancelled successfully)',
                'data' => $invoice->fresh(['items', 'patient', 'admission.room']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invoice: ' . $e->getMessage(),
            ], 500);
        }
    }


    // Process payment for an invoice
    public function processPayment(ProcessPaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            // lockForUpdate() blocks any other transaction from reading this
            // row until we commit/rollback — the second concurrent request
            // waits here, then sees the balance the first request just wrote.
            $invoice = Invoice::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($invoice->isCancelled()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'វិក្កយបត្រនេះត្រូវបានលុបចោល មិនអាចទទួលការទូទាត់បានទេ (This invoice is cancelled and cannot accept payment).',
                ], 422);
            }

            $paymentAmount = (float) $request->amount;

            // Authoritative overpayment guard, re-checked against the
            // locked (fresh) balance rather than the balance the client
            // last saw when the form was rendered.
            if ($paymentAmount > (float) $invoice->balance) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'ចំនួនប្រាក់ទូទាត់លើសពីសមតុល្យនៅសល់ (Amount exceeds the remaining balance).',
                ], 422);
            }

            $newPaid = $invoice->paid_amount + $paymentAmount;
            $newBalance = max(0, $invoice->total_amount - $newPaid);

            $status = 'unpaid';
            if ($newPaid >= $invoice->total_amount) {
                $status = 'paid';
            } elseif ($newPaid > 0) {
                $status = 'partial';
            }

            // Record transaction
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $paymentAmount,
                'payment_method' => $request->payment_method,
                'transaction_ref' => $request->transaction_ref ?? ('TXN-' . time()),
                'paid_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Update Invoice status and balance
            $invoice->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
                'status' => $status,
            ]);

            DB::commit();

            // Notify cashiers + admins that a payment was recorded
            $recipients = NotifiesRoles::usersForRoles(['admin', 'cashier']);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PaymentNotification($payment));
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ការទូទាត់ត្រូវបានបញ្ជាក់ដោយជោគជ័យ (Payment processed successfully)',
                    'invoice' => $invoice->fresh(['items', 'payments', 'patient', 'admission.room']),
                ]);
            }

            return redirect()->route('billing.index')->with('success', 'Payment processed successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // useful helper to generate a unique invoice number in the format INV-YYYYMMDD-XXX
    private function generateInvoiceNumber(): string
    {
        $datePrefix = date('Ymd');

        DB::statement(
            "INSERT INTO invoice_sequences (date_key, last_number, created_at, updated_at)
            VALUES (?, LAST_INSERT_ID(1), NOW(), NOW())
            ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)",
            [$datePrefix]
        );

        $nextSequence = (int) DB::getPdo()->lastInsertId();
        $sequenceStr = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        return "INV-{$datePrefix}-{$sequenceStr}";
    }
}
