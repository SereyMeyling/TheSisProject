<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>លេខវិក្កយបត្រ (Invoice ID)</th>
            <th>ឈ្មោះអ្នកជំងឺ (Patient Name)</th>
            <th>ប្រាក់សរុប (Total Fee)</th>
            <th>ប្រាក់បានបង់ (Paid)</th>
            <th>ប្រាក់ជំពាក់ (Balance)</th>
            <th>ស្ថានភាព (Status)</th>
            <th>កាលបរិច្ឆេទ (Date)</th>
            <th class="text-right">សកម្មភាព (Actions)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoices as $inv)
            <tr>
                <td class="font-weight-bold text-primary">{{ $inv->invoice_number }}</td>
                <td>
                    <div class="font-weight-bold">{{ $inv->patient_name }}</div>
                    <small class="text-muted">{{ $inv->patient_phone ?? '—' }}</small>
                </td>
                <td class="font-weight-bold">${{ number_format($inv->total_amount, 2) }}</td>
                <td class="text-success">${{ number_format($inv->paid_amount, 2) }}</td>
                <td class="text-danger">${{ number_format($inv->balance, 2) }}</td>
                <td>
                    @if ($inv->status === 'paid')
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>បានទូទាត់រួច (Paid)</span>
                    @elseif ($inv->status === 'partial')
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>បង់ខ្លះ (Partial)</span>
                    @else
                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>មិនទាន់បង់ (Unpaid)</span>
                    @endif
                </td>
                <td>{{ $inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '—' }}</td>
                <td>
                    <div class="action-icons justify-content-end">
                        {{-- Pay Now Button --}}
                        @if ($inv->status !== 'paid')
                            <button
                                type="button"
                                class="btn btn-icon btn-success btn-pay-now"
                                data-id="{{ $inv->id }}"
                                data-number="{{ $inv->invoice_number }}"
                                data-patient="{{ $inv->patient_name }}"
                                data-total="{{ $inv->total_amount }}"
                                data-paid="{{ $inv->paid_amount }}"
                                data-balance="{{ $inv->balance }}"
                                data-toggle="tooltip"
                                title="ទូទាត់ប្រាក់ (Pay Now)"
                            >
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                        @endif

                        {{-- View Detail Button --}}
                        <button
                            type="button"
                            class="btn btn-icon btn-outline-primary btn-view-detail"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើលព័ត៌មានលម្អិត (View Detail)"
                        >
                            <i class="fas fa-eye"></i>
                        </button>

                        {{-- View / Print Receipt Button --}}
                        <button
                            type="button"
                            class="btn btn-icon btn-outline-info btn-view-receipt"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើល / បោះពុម្ពវិក្កយបត្រ (View Receipt)"
                        >
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">មិនមានទិន្នន័យវិក្កយបត្រ (No Invoices Found)</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3">
    {!! $invoices->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
