<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * Middleware check: Only Admin or Cashier (or process-payments / view-invoices) can access.
     */
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->hasRole('admin') && !$user->hasRole('cashier') && !$user->hasPermissionTo('view-invoices') && !$user->hasPermissionTo('process-payments'))) {
                abort(403, 'អ្នកមិនមានសិទ្ធិចូលប្រើប្រាស់ទំព័រ Billing ទេ។ (You do not have permission to access the Billing module.)');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of invoices with search, filters, and summary stats.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['items', 'patient', 'payments']);

        // Search filter: invoice_number, patient_name, patient_phone
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'LIKE', $searchTerm)
                  ->orWhere('patient_name', 'LIKE', $searchTerm)
                  ->orWhere('patient_phone', 'LIKE', $searchTerm);
            });
        }

        // Status filter: unpaid, paid, partial
        if ($request->filled('status') && in_array($request->status, ['paid', 'unpaid', 'partial'])) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

        // Stats summary
        $totalInvoices = Invoice::count();
        $totalRevenue  = Invoice::sum('paid_amount');
        $totalUnpaid   = Invoice::sum('balance');

        $patients = Patient::select(['patient_id', 'full_name', 'phone', 'patient_code'])->get();

        if ($request->ajax()) {
            return response()->json([
                'html'          => view('billing.partials.table', compact('invoices'))->render(),
                'totalInvoices' => $totalInvoices,
                'totalRevenue'  => number_format($totalRevenue, 2),
                'totalUnpaid'   => number_format($totalUnpaid, 2),
            ]);
        }

        return view('billing.index', compact('invoices', 'totalInvoices', 'totalRevenue', 'totalUnpaid', 'patients'));
    }

    /**
     * Show detailed printable invoice receipt.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['items', 'patient', 'payments', 'creator'])->findOrFail($id);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $invoice,
                'html'    => view('billing.partials.receipt', compact('invoice'))->render(),
            ]);
        }

        return view('billing.show', compact('invoice'));
    }

    /**
     * Create a new invoice linking patient, items, and fees.
     */
    public function store(StoreInvoiceRequest $request)
    {
        DB::beginTransaction();
        try {
            // Generate unique invoice number: INV-YYYYMMDD-XXXX
            $invoiceNumber = $this->generateInvoiceNumber();

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['unit_price'];
                $totalAmount += $subtotal;
            }

            // Create Invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'patient_id'     => $request->patient_id,
                'patient_name'   => $request->patient_name,
                'patient_phone'  => $request->patient_phone,
                'total_amount'   => $totalAmount,
                'paid_amount'    => 0,
                'balance'        => $totalAmount,
                'status'         => 'unpaid',
                'notes'          => $request->notes,
                'created_by'     => auth()->id(),
            ]);

            // Create Invoice Items
            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['unit_price'];
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'item_type'   => $item['item_type'],
                    'description' => $item['description'],
                    'qty'         => $item['qty'],
                    'unit_price'  => $item['unit_price'],
                    'subtotal'    => $subtotal,
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'វិក្កយបត្រត្រូវបានបង្កើតដោយជោគជ័យ (Invoice created successfully)',
                    'data'    => $invoice->load(['items', 'patient']),
                ], 201);
            }

            return redirect()->route('billing.index')->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment (Cash, KHQR, or Card) for an invoice.
     */
    public function processPayment(ProcessPaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);

            $paymentAmount = (float) $request->amount;
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
                'invoice_id'      => $invoice->id,
                'amount'          => $paymentAmount,
                'payment_method'  => $request->payment_method,
                'transaction_ref' => $request->transaction_ref ?? ('TXN-' . time()),
                'paid_at'         => now(),
                'processed_by'    => auth()->id(),
            ]);

            // Update Invoice status and balance
            $invoice->update([
                'paid_amount' => $newPaid,
                'balance'     => $newBalance,
                'status'      => $status,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ការទូទាត់ត្រូវបានបញ្ជាក់ដោយជោគជ័យ (Payment processed successfully)',
                    'invoice' => $invoice->fresh(['items', 'payments', 'patient']),
                ]);
            }

            return redirect()->route('billing.index')->with('success', 'Payment processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate the next sequential invoice number for today: INV-YYYYMMDD-001
     */
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
        $sequenceStr  = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

        return "INV-{$datePrefix}-{$sequenceStr}";
    }
}
