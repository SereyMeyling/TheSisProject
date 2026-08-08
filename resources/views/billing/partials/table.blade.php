<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>លេខវិក្កយបត្រ (Invoice ID)</th>
            <th>ឈ្មោះអ្នកជំងឺ (Patient Name)</th>
            <th>ប្រភេទ (Visit)</th>
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
                <td class="font-weight-bold text-dark">{{ $inv->invoice_number }}</td>
                <td>
                    <div class="font-weight-bold">{{ $inv->patient_name }}</div>
                    <small class="text-muted">{{ $inv->patient_phone ?? '—' }}</small>
                </td>
                <td>
                    @if ($inv->admission_id)
                        <span class="badge badge-purple px-2 py-1" title="{{ optional($inv->admission->room)->room_number ? 'Room ' . $inv->admission->room->room_number : '' }}">
                            <i class="fas fa-bed mr-1"></i>IPD
                        </span>
                    @else
                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-walking mr-1"></i>OPD</span>
                    @endif
                </td>
                <td class="font-weight-bold">${{ number_format($inv->total_amount, 2) }}</td>
                <td class="text-value-positive">${{ number_format($inv->paid_amount, 2) }}</td>
                <td class="text-value-negative">${{ number_format($inv->balance, 2) }}</td>
                <td>
                    @if ($inv->status === 'paid')
                        <span class="badge badge-status badge-status-paid px-2 py-1"><i class="fas fa-check-circle mr-1"></i>បានទូទាត់រួច (Paid)</span>
                    @elseif ($inv->status === 'partial')
                        <span class="badge badge-status badge-status-partial px-2 py-1"><i class="fas fa-clock mr-1"></i>បង់ខ្លះ (Partial)</span>
                    @elseif ($inv->status === 'cancelled')
                        <span class="badge badge-status badge-status-cancelled px-2 py-1"><i class="fas fa-ban mr-1"></i>បានលុបចោល (Cancelled)</span>
                    @else
                        <span class="badge badge-status badge-status-unpaid px-2 py-1"><i class="fas fa-times-circle mr-1"></i>មិនទាន់បង់ (Unpaid)</span>
                    @endif
                </td>
                <td class="text-dark">{{ $inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '—' }}</td>
                <td>
                    <div class="action-icons justify-content-end">
                        {{-- Pay Now Button --}}
                        @if ($inv->status !== 'paid' && $inv->status !== 'cancelled')
                            <button
                                type="button"
                                class="btn btn-icon btn-outline-success btn-pay-now"
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
                            class="btn btn-sm btn-outline-info btn-view-detail"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើលព័ត៌មានលម្អិត (View Detail)"
                        >
                            <i class="fas fa-list-alt"></i>
                        </button>

                        {{-- View / Print Receipt Button --}}
                        <button
                            type="button"
                            class="btn btn-icon btn-outline-secondary btn-view-receipt"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើល / បោះពុម្ពវិក្កយបត្រ (View Receipt)"
                        >
                            <i class="fas fa-print"></i>
                        </button>

                        {{-- Edit Button: only when unpaid, and only for admins / users with edit-invoices permission --}}
                        @if ($inv->status === 'unpaid' && (auth()->user()->hasRole('admin') || auth()->user()->can('edit-invoices')))
                            <button
                                type="button"
                                class="btn btn-icon btn-outline-warning btn-edit-invoice"
                                data-id="{{ $inv->id }}"
                                data-toggle="tooltip"
                                title="កែប្រែវិក្កយបត្រ (Edit Invoice)"
                                style="display:none;"
                            >
                                <i class="fas fa-pen"></i>
                            </button>
                        @endif

                        {{-- Cancel Button: only when unpaid, and only for admins / users with cancel-invoices permission --}}
                        @if ($inv->status === 'unpaid' && (auth()->user()->hasRole('admin') || auth()->user()->can('cancel-invoices')))
                            <button
                                type="button"
                                class="btn btn-icon btn-outline-danger btn-cancel-invoice"
                                data-id="{{ $inv->id }}"
                                data-number="{{ $inv->invoice_number }}"
                                data-toggle="tooltip"
                                title="លុបចោលវិក្កយបត្រ (Cancel Invoice)"
                            >
                                <i class="fas fa-ban"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="fas fa-file-invoice fa-2x d-block mb-2 text-muted"></i>
                    មិនមានទិន្នន័យវិក្កយបត្រ (No Invoices Found)
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3 pb-2">
    {!! $invoices->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
