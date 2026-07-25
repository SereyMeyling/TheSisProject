<div class="printable-receipt p-3" id="receiptPrintArea">
    <!-- Receipt Header -->
    <div class="text-center mb-4 pb-3 border-bottom">
        <h3 class="font-weight-bold text-uppercase mb-1" style="color: #198754;">
            <i class="fas fa-clinic-medical mr-2"></i>PrumSantepheap Hospital
        </h3>
        <p class="text-muted mb-0 small">មន្ទីរពេទ្យ ព្រំសន្តិភាព &bull; Hospital Management System</p>
        <p class="text-muted small mb-0">អាសយដ្ឋាន: រាជធានីភ្នំពេញ | ទូរស័ព្ទ: (+855) 12 345 678</p>
        <div class="badge badge-secondary mt-2 px-3 py-1 font-weight-normal">ប័ណ្ណទូទាត់ប្រាក់ / INVOICE RECEIPT</div>
    </div>

    <!-- Patient & Invoice Metadata -->
    <div class="row mb-3">
        <div class="col-6">
            <p class="mb-1"><strong>លេខវិក្កយបត្រ (Invoice No):</strong> <span class="text-primary">{{ $invoice->invoice_number }}</span></p>
            <p class="mb-1"><strong>ឈ្មោះអ្នកជំងឺ (Patient):</strong> {{ $invoice->patient_name }}</p>
            <p class="mb-1"><strong>លេខទូរស័ព្ទ (Phone):</strong> {{ $invoice->patient_phone ?? 'N/A' }}</p>
        </div>
        <div class="col-6 text-right">
            <p class="mb-1"><strong>កាលបរិច្ឆេទ (Date):</strong> {{ $invoice->created_at ? $invoice->created_at->format('d/m/Y H:i') : '' }}</p>
            <p class="mb-1"><strong>អ្នកគិតប្រាក់ (Cashier):</strong> {{ optional($invoice->creator)->name ?? auth()->user()->name }}</p>
            <p class="mb-1">
                <strong>ស្ថានភាព (Status):</strong>
                @if($invoice->status === 'paid')
                    <span class="badge badge-success">បានទូទាត់រួច (PAID)</span>
                @elseif($invoice->status === 'partial')
                    <span class="badge badge-warning text-dark">បង់ខ្លះ (PARTIAL)</span>
                @else
                    <span class="badge badge-danger">មិនទាន់បង់ (UNPAID)</span>
                @endif
            </p>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered table-sm mb-3">
        <thead class="bg-light">
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
                                <i class="fas fa-check text-success mr-1"></i>
                                {{ ucfirst($p->payment_method) }}: ${{ number_format($p->amount, 2) }} ({{ $p->paid_at ? $p->paid_at->format('d/m/Y H:i') : '' }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-6">
            <table class="table table-sm borderless mb-0 text-right">
                <tr>
                    <td><strong>សរុបទាំងអស់ (Grand Total):</strong></td>
                    <td class="font-weight-bold text-dark">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>ប្រាក់បានបង់ (Total Paid):</strong></td>
                    <td class="font-weight-bold text-success">${{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="border-top">
                    <td><strong>ប្រាក់នៅខ្វះ (Balance Due):</strong></td>
                    <td class="font-weight-bold text-danger font-size-16">${{ number_format($invoice->balance, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 text-center mt-3" style="border-top: 1px dashed #ccc;">
        <div class="col-6">
            <p class="mb-5 small text-muted">ហត្ថលេខាអ្នកជំងឺ / Patient Signature</p>
            <p class="mb-0 text-muted">___________________________</p>
        </div>
        <div class="col-6">
            <p class="mb-5 small text-muted">ហត្ថលេខាអ្នកគិតប្រាក់ / Cashier Signature</p>
            <p class="mb-0 font-weight-bold">{{ optional($invoice->creator)->name ?? auth()->user()->name }}</p>
        </div>
    </div>
</div>
