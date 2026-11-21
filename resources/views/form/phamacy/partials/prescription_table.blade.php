<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>ល.រ (ID)</th>
            <th>កាលបរិច្ឆេទ</th>
            <th>អ្នកជំងឺ (Patient)</th>
            <th>វេជ្ជបណ្ឌិត (Doctor)</th>
            <th>បញ្ជីថ្នាំដែលត្រូវប្រើ (Prescription Items)</th>
            <th>សកម្មភាព (Actions)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($prescriptions as $pres)
            @php $formId = 'dispense-form-' . $pres->prescription_id; @endphp
            <tr>
                <td>{{ $pres->prescription_id }}</td>
                <td>
                    <div class="font-weight-bold">
                        <i class="far fa-calendar-alt text-info mr-1"></i>
                        {{ $pres->prescribed_date ? $pres->prescribed_date->format('d M, Y h:i A') : '-' }}
                    </div>
                </td>
                <td>
                    <div class="font-weight-bold text-dark">
                        {{ $pres->medicalRecord && $pres->medicalRecord->patient ? $pres->medicalRecord->patient->full_name : 'N/A' }}
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-phone mr-1"></i>
                        {{ $pres->medicalRecord && $pres->medicalRecord->patient ? $pres->medicalRecord->patient->phone : '-' }}
                    </small>
                </td>
                <td>
                    <div class="font-weight-bold text-primary">
                        <i class="fas fa-user-md mr-1"></i>
                        {{ $pres->medicalRecord && $pres->medicalRecord->doctor
            ? $pres->medicalRecord->doctor->name
            : 'N/A' }}
                    </div>
                </td>
                <td>
                    <ul class="list-unstyled mb-0">
                        @foreach ($pres->items as $item)
                            @php
                                // Prescriptions dispensed before partial dispensing existed have no dispense rows:
                                // the old code only marked them dispensed once every item was given in full.
                                $legacy = $pres->status === 'dispensed' && $item->dispenses->isEmpty();
                                $given = $legacy ? (int) $item->quantity : $item->dispensed_quantity;
                                $left = max(0, (int) $item->quantity - $given);
                            @endphp
                            <li class="mb-2">
                                <span class="badge badge-light border text-dark font-weight-bold">
                                    {{ $item->medicine ? $item->medicine->medicine_name : 'Medicine #' . $item->medicine_id }}
                                </span>
                                <small class="text-muted">
                                    ({{ $item->quantity }} {{ $item->medicine ? $item->medicine->unit : 'unit' }}) -
                                    {{ $item->dosage }}, {{ $item->frequency }}, {{ $item->duration_days }} ថ្ងៃ
                                </small>

                                @if ($left === 0)
                                    <span class="badge badge-success">ចេញរួច</span>
                                @else
                                    @if ($given > 0)
                                        <span class="badge badge-warning">ចេញហើយ {{ $given }}/{{ $item->quantity }}</span>
                                    @endif

                                    @if ($pres->status !== 'dispensed')
                                        <span class="ml-2">
                                            <small class="text-muted">ចេញឥឡូវ:</small>
                                            <input type="number" form="{{ $formId }}"
                                                name="items[{{ $item->prescription_item_id }}]" value="{{ $left }}"
                                                min="0" max="{{ $left }}"
                                                class="form-control form-control-sm d-inline-block"
                                                style="width: 80px;">
                                        </span>
                                    @endif
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    @if ($pres->status === 'dispensed')

                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                            <i class="fas fa-check-circle mr-1"></i>
                            បានចេញថ្នាំរួច
                        </button>

                    @else

                        @if ($pres->status === 'partial')
                            <div class="small text-warning mb-1">ចេញថ្នាំខ្លះរួចហើយ</div>
                        @endif

                        <form id="{{ $formId }}"
                            action="{{ route('pharmacy.prescriptions.dispense', $pres->prescription_id) }}" method="POST"
                            class="d-inline">

                            @csrf

                            <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('តើអ្នកពិតជាចង់ចេញថ្នាំតាមចំនួនដែលបានបញ្ចូលមែនទេ? (Confirm Dispense)');">

                                <i class="fas fa-pills mr-1"></i>
                                ចេញថ្នាំ (Dispense)

                            </button>

                        </form>

                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">មិនមានទិន្នន័យវេជ្ជបញ្ជាទេ (No Prescriptions Found)</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3">
    {!! $prescriptions->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
