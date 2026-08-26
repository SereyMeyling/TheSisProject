<div class="printable-receipt p-4" id="receiptPrintArea">

    <style>
        /* Scoped styles for the printable receipt — self-contained since this
           partial is injected via AJAX into the receipt modal body. */
        #receiptPrintArea {
            --receipt-blue: #2563a8;
            --receipt-gray-600: #6b7280;
            --receipt-gray-200: #e5e7ea;
            --receipt-green: #1e8a4c;
            --receipt-red: #c0392b;
            font-size: .9rem;
            color: #33383f;
        }
        #receiptPrintArea .receipt-header h3 {
            color: var(--receipt-blue) !important;
            font-size: 1.25rem;
            letter-spacing: .02em;
        }
        #receiptPrintArea .receipt-tag {
            background: #eaf1f8;
            color: var(--receipt-blue);
            font-weight: 500;
            font-size: .72rem;
            letter-spacing: .03em;
            border-radius: 4px;
        }
        #receiptPrintArea .meta-label {
            color: var(--receipt-gray-600);
            font-weight: 600;
            font-size: .78rem;
        }
        #receiptPrintArea .table { font-size: .85rem; }
        #receiptPrintArea .table thead th {
            background: #f8f9fa;
            color: var(--receipt-gray-600);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-weight: 600;
            border-color: var(--receipt-gray-200);
        }
        #receiptPrintArea .table td { border-color: var(--receipt-gray-200); vertical-align: middle; }
        #receiptPrintArea .badge-purple { background-color: #006D36; color: #fff; font-weight: 500; }
        #receiptPrintArea .badge-status-paid      { background: #e7f5ed; color: var(--receipt-green); }
        #receiptPrintArea .badge-status-partial   { background: #fdf3e2; color: #a3690c; }
        #receiptPrintArea .badge-status-unpaid    { background: #fbeceb; color: var(--receipt-red); }
        #receiptPrintArea .badge-status-cancelled { background: #f1f2f4; color: var(--receipt-gray-600); }
        #receiptPrintArea .totals-table td { padding: .3rem 0; border: none; }
        #receiptPrintArea .totals-table .balance-row td {
            border-top: 1px solid var(--receipt-gray-200);
            padding-top: .5rem;
        }
        #receiptPrintArea .text-value-positive { color: var(--receipt-green) !important; }
        #receiptPrintArea .text-value-negative { color: var(--receipt-red) !important; }
        #receiptPrintArea .signature-block { border-top: 1px dashed var(--receipt-gray-200); }
        #receiptPrintArea .signature-line { color: var(--receipt-gray-600); }
    </style>

    <!-- Receipt Header -->
    <div class="receipt-header text-center mb-4 pb-3 border-bottom">
        <h3 class="font-weight-bold text-uppercase mb-1">
            <i class="fas fa-clinic-medical mr-2"></i>PrumSantepheap Hospital
        </h3>
        <p class="text-muted mb-0 small">មន្ទីរពេទ្យ ព្រំសន្តិភាព &bull; Hospital Management System</p>
        <p class="text-muted small mb-2">អាសយដ្ឋាន: រាជធានីភ្នំពេញ | ទូរស័ព្ទ: (+855) 12 345 678</p>
        <span class="badge receipt-tag px-3 py-1">ប័ណ្ណទូទាត់ប្រាក់ / INVOICE RECEIPT</span>
    </div>

    @if($invoice->status === 'cancelled')
        <div class="alert alert-secondary text-center mb-3">
            <i class="fas fa-ban mr-1"></i>
            <strong>វិក្កយបត្រនេះត្រូវបានលុបចោល (THIS INVOICE HAS BEEN CANCELLED)</strong>
            @if($invoice->cancel_reason)
                <div class="small mt-1">មូលហេតុ (Reason): {{ $invoice->cancel_reason }}</div>
            @endif
            <div class="small">
                {{ optional($invoice->canceller)->name ?? '—' }}
                @if($invoice->cancelled_at) &bull; {{ $invoice->cancelled_at->format('d/m/Y H:i') }} @endif
            </div>
        </div>
    @endif

    <!-- Patient & Invoice Metadata -->
    <div class="row mb-3">
        <div class="col-6">
            <p class="mb-1"><span class="meta-label">លេខវិក្កយបត្រ (Invoice No):</span> <span class="text-dark font-weight-bold">{{ $invoice->invoice_number }}</span></p>
            <p class="mb-1"><span class="meta-label">ឈ្មោះអ្នកជំងឺ (Patient):</span> {{ $invoice->patient_name }}</p>
            <p class="mb-1"><span class="meta-label">លេខទូរស័ព្ទ (Phone):</span> {{ $invoice->patient_phone ?? 'N/A' }}</p>
            <p class="mb-1">
                <span class="meta-label">ប្រភេទចូលពិនិត្យ:</span>
                @if($invoice->admission_id)
                    <span class="badge badge-purple">
                        សម្រាកពេទ្យ (IPD)
                    </span>

                    @if(optional($invoice->admission->room)->room_number)
                        — បន្ទប់ {{ $invoice->admission->room->room_number }}
                    @endif

                @else
                    <span class="badge badge-secondary">
                        ពិនិត្យក្រៅ (OPD)
                    </span>
                @endif
            </p>
        </div>
        <div class="col-6 text-right">
            <p class="mb-1"><span class="meta-label">កាលបរិច្ឆេទ (Date):</span> {{ $invoice->created_at ? $invoice->created_at->format('d/m/Y g:i:s A') : '' }}</p>
            <p class="mb-1"><span class="meta-label">អ្នកគិតប្រាក់ (Cashier):</span> {{ optional($invoice->creator)->name ?? auth()->user()->name }}</p>
            <p class="mb-1">
                <span class="meta-label">ស្ថានភាព (Status):</span>
                @if($invoice->status === 'paid')
                    <span class="badge badge-status-paid">
                        បានបង់រួចរាល់
                    </span>
                @elseif($invoice->status === 'partial')
                    <span class="badge badge-status-partial">
                        បង់បានខ្លះ
                    </span>
                @elseif($invoice->status === 'cancelled')
                    <span class="badge badge-status-cancelled">
                        បានលុបចោល
                    </span>
                @else
                    <span class="badge badge-status-unpaid">
                        មិនទាន់បង់
                    </span>
                @endif
            </p>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered table-sm mb-3">
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th>ប្រភេទសេវា (Type)</th>
                <th>បរិយាយ (Description)</th>
                <th class="text-center" style="width: 70px;">ចំនួន</th>
                <th class="text-right" style="width: 100px;">តម្លៃរាយ</th>
                <th class="text-right" style="width: 110px;">សរុប</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <span class="badge badge-light border text-uppercase">{{ $item->item_type }}</span>
                    </td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right font-weight-bold">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">គ្មានព័ត៌មានសេវាកម្ម</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals Summary -->
    <div class="row mb-4">
        <div class="col-6">
            @if($invoice->notes)
                <div class="p-2 bg-light border rounded small">
                    <strong>ចំណាំ (Notes):</strong> {{ $invoice->notes }}
                </div>
            @endif

            @if($invoice->payments->count() > 0)
                <div class="mt-2">
                    <small class="font-weight-bold text-muted">ប្រវត្តិទូទាត់ (Payment History):</small>
                    <ul class="list-unstyled mb-0 small">
                        @foreach($invoice->payments as $p)
                            <li>
                                <i class="fas fa-check text-value-positive mr-1"></i>
                                {{ ucfirst($p->payment_method) }}: ${{ number_format($p->amount, 2) }}
                                ({{ $p->paid_at ? $p->paid_at->format('d/m/Y H:i') : '' }})
                                @if($p->transaction_ref)
                                    <span class="text-muted">— {{ $p->transaction_ref }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-6">
            <table class="table table-sm borderless totals-table mb-0 text-right">
                <tr>
                    <td><strong>សរុបទាំងអស់ (Grand Total):</strong></td>
                    <td class="font-weight-bold text-dark">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>ប្រាក់បានបង់ (Total Paid):</strong></td>
                    <td class="font-weight-bold text-value-positive">${{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="balance-row">
                    <td><strong>ប្រាក់នៅខ្វះ (Balance Due):</strong></td>
                    <td class="font-weight-bold text-value-negative font-size-16">${{ number_format($invoice->balance, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row signature-block pt-4 text-center mt-3">
        <div class="col-6">
            <p class="mb-5 small signature-line">ហត្ថលេខាអ្នកជំងឺ / Patient Signature</p>
            <p class="mb-0 signature-line">___________________________</p>
        </div>
        <div class="col-6">
            <p class="mb-5 small signature-line">ហត្ថលេខាអ្នកគិតប្រាក់ / Cashier Signature</p>
            <p class="mb-0 font-weight-bold">{{ optional($invoice->creator)->name ?? auth()->user()->name }}</p>
        </div>
    </div>
</div>
