<table class="table align-middle mb-0">
    <thead class="bg-light">
        <tr>
            <th>លេខវិក្កយបត្រ</th>
            <th>អ្នកជំងឺ</th>
            <th>ប្រភេទ</th>
            <th>ប្រាក់សរុប</th>
            <th>បានបង់</th>
            <th>ជំពាក់</th>
            <th>ស្ថានភាព</th>
            <th>កាលបរិច្ឆេទ</th>
            <th class="text-right">សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoices as $inv)
            <tr>
                <td class="font-weight-bold text-primary">
                    <i class="fas fa-file-invoice mr-1 text-muted"></i>{{ $inv->invoice_number }}
                </td>
                <td>
                    <div class="font-weight-bold text-dark">{{ $inv->patient_name }}</div>
                    <small class="text-muted"><i class="fas fa-phone mr-1"></i>{{ $inv->patient_phone ?? '—' }}</small>
                </td>
                <td>
                    @if ($inv->admission_id)
                        <span class="badge badge-purple px-2 py-1" style="background: #eedefc; color: #6b21a8; border-radius: 8px;" title="{{ optional($inv->admission->room)->room_number ? 'បន្ទប់ ' . $inv->admission->room->room_number : '' }}">
                            <i class="fas fa-bed mr-1"></i>IPD (បន្ទប់ {{ optional($inv->admission->room)->room_number ?? '—' }})
                        </span>
                    @else
                        <span class="badge badge-secondary px-2 py-1" style="background: #f1f5f9; color: #475569; border-radius: 8px;"><i class="fas fa-walking mr-1"></i>OPD</span>
                    @endif
                </td>
                <td class="font-weight-bold text-dark">${{ number_format($inv->total_amount, 2) }}</td>
                <td class="font-weight-bold text-success">${{ number_format($inv->paid_amount, 2) }}</td>
                <td class="font-weight-bold {{ $inv->balance > 0 ? 'text-danger' : 'text-muted' }}">${{ number_format($inv->balance, 2) }}</td>
                <td>
                    @if ($inv->status === 'paid')
                        <span class="badge badge-success px-2 py-1" style="border-radius: 8px;"><i class="fas fa-check-circle mr-1"></i>បានទូទាត់រួច</span>
                    @elseif ($inv->status === 'partial')
                        <span class="badge badge-warning text-dark px-2 py-1" style="border-radius: 8px;"><i class="fas fa-clock mr-1"></i>បង់ខ្លះ</span>
                    @elseif ($inv->status === 'cancelled')
                        <span class="badge badge-secondary px-2 py-1" style="border-radius: 8px;"><i class="fas fa-ban mr-1"></i>បានលុបចោល</span>
                    @else
                        <span class="badge badge-danger px-2 py-1" style="border-radius: 8px;"><i class="fas fa-times-circle mr-1"></i>មិនទាន់បង់</span>
                    @endif
                </td>
                <td class="text-muted small">
                    <i class="far fa-calendar-alt mr-1"></i>{{ $inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '—' }}
                </td>
                <td>
                    <div class="d-flex justify-content-end align-items-center" style="gap: 4px;">
                        {{-- Pay Now Button --}}
                        @if ($inv->status !== 'paid' && $inv->status !== 'cancelled')
                            <button
                                type="button"
                                class="btn btn-sm btn-success btn-pay-now"
                                data-id="{{ $inv->id }}"
                                data-number="{{ $inv->invoice_number }}"
                                data-patient="{{ $inv->patient_name }}"
                                data-total="{{ $inv->total_amount }}"
                                data-paid="{{ $inv->paid_amount }}"
                                data-balance="{{ $inv->balance }}"
                                data-toggle="tooltip"
                                title="ទូទាត់ប្រាក់"
                                style="border-radius: 8px;"
                            >
                                <i class="fas fa-dollar-sign mr-1"></i> បង់ប្រាក់
                            </button>
                        @endif

                        {{-- View Detail Button --}}
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-info btn-view-detail"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើលព័ត៌មានលម្អិត"
                            style="border-radius: 8px;"
                        >
                            <i class="fas fa-list-alt"></i>
                        </button>

                        {{-- View / Print Receipt Button --}}
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary btn-view-receipt"
                            data-id="{{ $inv->id }}"
                            data-toggle="tooltip"
                            title="មើល / បោះពុម្ពវិក្កយបត្រ"
                            style="border-radius: 8px;"
                        >
                            <i class="fas fa-print"></i>
                        </button>

                        {{-- Cancel Button: only when unpaid, and only for admins / cashiers --}}
                        @if ($inv->status === 'unpaid' && (auth()->user()->hasRole('admin') || auth()->user()->can('cancel-invoices')))
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-cancel-invoice"
                                data-id="{{ $inv->id }}"
                                data-number="{{ $inv->invoice_number }}"
                                data-toggle="tooltip"
                                title="លុបចោលវិក្កយបត្រ"
                                style="border-radius: 8px;"
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
                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-muted opacity-50"></i>
                    <p class="font-weight-bold mb-1">មិនមានទិន្នន័យវិក្កយបត្រទេ</p>
                    <small>សូមជ្រើសរើសពាក្យស្វែងរកផ្សេង ឬបង្កើតវិក្កយបត្រថ្មី</small>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3 pb-2">
    {!! $invoices->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
