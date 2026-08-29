<table class="table align-middle mb-0">
    <thead class="bg-light">
        <tr>
            <th>លេខការកម្មង់</th>
            <th>កាលបរិច្ឆេទ</th>
            <th>អ្នកជំងឺ</th>
            <th>វេជ្ជបណ្ឌិត</th>
            <th>បញ្ជីតេស្តត្រូវពិនិត្យ</th>
            <th>ស្ថានភាព</th>
            <th class="text-right">សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($labOrders as $ord)
            <tr>
                <td class="font-weight-bold text-primary">#LAB-{{ $ord->lab_order_id }}</td>
                <td>
                    <div class="font-weight-bold small text-dark">
                        <i class="far fa-calendar-alt text-info mr-1"></i>
                        {{ $ord->order_date ? $ord->order_date->format('d/m/Y H:i') : '—' }}
                    </div>
                </td>
                <td>
                    <div class="font-weight-bold text-dark">
                        {{ $ord->medicalRecord && $ord->medicalRecord->patient ? $ord->medicalRecord->patient->full_name : '—' }}
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-id-badge mr-1"></i>{{ $ord->medicalRecord && $ord->medicalRecord->patient ? $ord->medicalRecord->patient->patient_code : '' }}
                    </small>
                </td>
                <td>
                    <div class="font-weight-bold text-primary small">
                        <i class="fas fa-user-md mr-1"></i>
                        {{ $ord->medicalRecord && $ord->medicalRecord->doctor ? $ord->medicalRecord->doctor->first_name . ' ' . $ord->medicalRecord->doctor->last_name : '—' }}
                    </div>
                </td>
                <td>
                    <ul class="list-unstyled mb-0">
                        @foreach ($ord->results as $res)
                            <li class="mb-1">
                                <span class="badge badge-light border text-dark font-weight-bold">
                                    {{ $res->labTest ? $res->labTest->test_name : 'Test #' . $res->test_id }}
                                </span>
                                @if($res->result_value && $res->result_value !== 'Pending')
                                    <span class="badge badge-success px-2 py-1 ml-1">{{ $res->result_value }}</span>
                                @else
                                    <span class="badge badge-warning text-dark px-2 py-1 ml-1">កំពុងរង់ចាំ</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    @if ($ord->status === 'completed')
                        <span class="badge badge-success px-2 py-1" style="border-radius: 8px;"><i class="fas fa-check-circle mr-1"></i>បានបញ្ចប់</span>
                    @else
                        <span class="badge badge-warning text-dark px-2 py-1" style="border-radius: 8px;"><i class="fas fa-clock mr-1"></i>កំពុងរង់ចាំ</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex justify-content-end align-items-center" style="gap: 4px;">
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-enter-results"
                            data-id="{{ $ord->lab_order_id }}"
                            data-results='@json($ord->results)'
                            data-patient="{{ $ord->medicalRecord && $ord->medicalRecord->patient ? $ord->medicalRecord->patient->full_name : '' }}"
                            style="border-radius: 8px;"
                        >
                            <i class="fas fa-vial mr-1"></i> បញ្ចូលលទ្ធផល
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fas fa-vials fa-3x mb-3 text-muted opacity-50"></i>
                    <p class="font-weight-bold mb-1">មិនមានទិន្នន័យការកម្មង់ពិនិត្យទេ</p>
                    <small>សូមបង្កើតការកម្មង់ពិនិត្យមន្ទីរពិសោធន៍ថ្មី</small>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center pagination-wrapper mt-3 pb-2">
    {!! $labOrders->appends(request()->query())->links('pagination::bootstrap-4') !!}
</div>
