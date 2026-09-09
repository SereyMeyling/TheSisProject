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
                        <span class="badge badge-purple px-2 py-1"
                            title="{{ optional($inv->admission->room)->room_number ? 'Room ' . $inv->admission->room->room_number : '' }}">
                            <i class="fas fa-bed mr-1"></i>IPD
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
<<<<<<< HEAD
                        <span class="badge badge-status badge-status-paid px-2 py-1"><i
                                class="fas fa-check-circle mr-1"></i>បានទូទាត់រួច (Paid)</span>
                    @elseif ($inv->status === 'partial')
                        <span class="badge badge-status badge-status-partial px-2 py-1"><i class="fas fa-clock mr-1"></i>បង់ខ្លះ
                            (Partial)</span>
                    @elseif ($inv->status === 'cancelled')
                        <span class="badge badge-status badge-status-cancelled px-2 py-1"><i
                                class="fas fa-ban mr-1"></i>បានលុបចោល (Cancelled)</span>
                    @else
                        <span class="badge badge-status badge-status-unpaid px-2 py-1"><i
                                class="fas fa-times-circle mr-1"></i>មិនទាន់បង់ (Unpaid)</span>
=======
                        <span class="badge badge-success px-2 py-1" style="border-radius: 8px;"><i class="fas fa-check-circle mr-1"></i>បានទូទាត់រួច</span>
                    @elseif ($inv->status === 'partial')
                        <span class="badge badge-warning text-dark px-2 py-1" style="border-radius: 8px;"><i class="fas fa-clock mr-1"></i>បង់ខ្លះ</span>
                    @elseif ($inv->status === 'cancelled')
                        <span class="badge badge-secondary px-2 py-1" style="border-radius: 8px;"><i class="fas fa-ban mr-1"></i>បានលុបចោល</span>
                    @else
                        <span class="badge badge-danger px-2 py-1" style="border-radius: 8px;"><i class="fas fa-times-circle mr-1"></i>មិនទាន់បង់</span>
>>>>>>> origin/vannaTask
                    @endif
                </td>
                <td class="text-muted small">
                    <i class="far fa-calendar-alt mr-1"></i>{{ $inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '—' }}
                </td>
                <td>
                    <div class="action-icons justify-content-center">

                        {{-- Pay Now --}}
                        @if ($inv->status !== 'paid' && $inv->status !== 'cancelled')
                            <button type="button" class="btn btn-icon btn-outline-success btn-pay-now" data-id="{{ $inv->id }}"
                                data-number="{{ $inv->invoice_number }}" data-patient="{{ $inv->patient_name }}"
                                data-total="{{ $inv->total_amount }}" data-paid="{{ $inv->paid_amount }}"
                                data-balance="{{ $inv->balance }}" data-toggle="tooltip" title="ទូទាត់ប្រាក់ (Pay Now)">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                        @endif

                        {{-- Three dots --}}
                        <div class="dropdown">
                            <button type="button" class="btn btn-icon btn-outline-secondary" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false" title="សកម្មភាពបន្ថែម">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-right">

                                {{-- View Detail --}}
                                <button type="button" class="dropdown-item btn-view-detail" data-id="{{ $inv->id }}">
                                    <i class="fas fa-list-alt text-info mr-2"></i>
                                    មើលព័ត៌មានលម្អិត
                                </button>

                                {{-- View / Print Receipt --}}
                                <button type="button" class="dropdown-item btn-view-receipt" data-id="{{ $inv->id }}">
                                    <i class="fas fa-print text-secondary mr-2"></i>
                                    មើល / បោះពុម្ពវិក្កយបត្រ
                                </button>

                                {{-- Edit --}}
                                @if (
                                        $inv->status === 'unpaid' &&
                                        (auth()->user()->hasRole('admin') || auth()->user()->can('edit-invoices'))
                                    )
                                    <button type="button" class="dropdown-item btn-edit-invoice" data-id="{{ $inv->id }}">
                                        <i class="fas fa-pen text-warning mr-2"></i>
                                        កែប្រែវិក្កយបត្រ
                                    </button>
                                @endif

                                {{-- Cancel --}}
                                @if (
                                        $inv->status === 'unpaid' &&
                                        (auth()->user()->hasRole('admin') || auth()->user()->can('cancel-invoices'))
                                    )
                                    <button type="button" class="dropdown-item btn-cancel-invoice" data-id="{{ $inv->id }}"
                                        data-number="{{ $inv->invoice_number }}">
                                        <i class="fas fa-ban text-danger mr-2"></i>
                                        លុបចោលវិក្កយបត្រ
                                    </button>
                                @endif

                            </div>
                        </div>

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
