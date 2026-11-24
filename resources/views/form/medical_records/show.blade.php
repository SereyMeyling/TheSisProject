@extends('adminlte::page')

@section('title', 'Medical Record #MR-' . $record->record_id)

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
        <i class="fas fa-file-invoice text-success mr-2"></i> ព័ត៌មានលម្អិត #MR-{{ $record->record_id }}
    </h1>
    <div>
        <a href="{{ route('medical-records.index') }}" class="btn btn-secondary px-3 rounded-pill shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
        </a>
        @if($canModify)
            <a href="{{ route('medical-records.edit', $record->record_id) }}"
                class="btn btn-primary px-3 rounded-pill shadow-sm ml-1" style="background-color: #00695c;">
                <i class="fas fa-edit mr-1"></i> កែប្រែ
            </a>
            <button type="button" class="btn btn-danger px-3 rounded-pill shadow-sm ml-1" data-toggle="modal"
                data-target="#deleteModal">
                <i class="fas fa-trash-alt mr-1"></i> លុប
            </button>
        @endif
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-lg">
        <div class="card-body p-4">
            <div class="row border-bottom pb-3 mb-3">
                <div class="col-md-6">
                    <h5 class="font-weight-bold text-success">{{ $record->patient->full_name ?? 'N/A' }}</h5>
                    <p class="text-muted mb-0">លេខកូដអ្នកជំងឺ: {{ $record->patient->patient_code ?? 'N/A' }}</p>
                    <p class="text-muted mb-0">ភេទ: {{ $record->patient->sex ?? '-' }} | អាយុ:
                        {{ $record->patient->age ?? '-' }} ឆ្នាំ</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-1"><strong>កាលបរិច្ឆេទពិនិត្យ:</strong>
                        {{ $record->visit_date ? $record->visit_date->format('d/m/Y H:i A') : '-' }}</p>
                    <p class="mb-0"><strong>គ្រូពេទ្យទទួលខុសត្រូវ:</strong> {{ $record->doctor->name ?? 'N/A' }}</p>
                </div>
            </div>

            <h6 class="font-weight-bold text-dark mb-3">
                <i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)
            </h6>
            <div class="row text-center mb-3">
                @foreach([
                    ['BP', ($record->bp_systolic ?? '-') . '/' . ($record->bp_diastolic ?? '-'), 'mmHg'],
                    ['Heart Rate', $record->heart_rate, 'bpm'],
                    ['Resp. Rate', $record->respiratory_rate, ''],
                    ['Temp', $record->temperature, '°C'],
                    ['SpO2', $record->spo2, '%'],
                    ['Weight', $record->weight, 'kg'],
                ] as [$label, $value, $unit])
                    <div class="col-md-2 col-4 mb-2">
                        <div class="p-2 bg-light rounded border">
                            <small class="text-muted d-block">{{ $label }}</small>
                            <strong>{{ $value === null || $value === '' ? '-' : $value }}</strong> <small>{{ $unit }}</small>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mb-3">
                <h6 class="font-weight-bold text-dark">ការវិនិច្ឆ័យរោគ (Diagnosis)</h6>
                <div class="p-3 bg-light rounded border" style="white-space: pre-line;">{{ $record->diagnosis ?: 'គ្មានការវិនិច្ឆ័យ' }}</div>
            </div>
            <div class="mb-3">
                <h6 class="font-weight-bold text-dark">ចំណាំបន្ថែម (Notes)</h6>
                <div class="p-3 bg-light rounded border" style="white-space: pre-line;">{{ $record->notes ?: 'គ្មានចំណាំ' }}</div>
            </div>
            @if($record->prescription_notes)
                <div class="mb-3">
                    <h6 class="font-weight-bold text-dark">ចំណាំវេជ្ជបញ្ជា (Prescription notes)</h6>
                    <div class="p-3 bg-light rounded border" style="white-space: pre-line;">{{ $record->prescription_notes }}</div>
                </div>
            @endif

            <h6 class="font-weight-bold text-dark mb-2 mt-4">
                <i class="fas fa-vials text-primary mr-2"></i> ការពិនិត្យមន្ទីរពិសោធន៍ (Lab Orders)
            </h6>
            @forelse($record->labOrders as $order)
                <div class="border rounded mb-2">
                    <div class="bg-light px-3 py-2 d-flex justify-content-between">
                        <span class="font-weight-bold text-primary">#LAB-{{ $order->lab_order_id }}
                            <small class="text-muted ml-2">{{ $order->order_date ? $order->order_date->format('d/m/Y H:i') : '' }}</small>
                        </span>
                        @if($order->status === 'completed')
                            <span class="badge badge-success px-2 py-1">បានបញ្ចប់</span>
                        @else
                            <span class="badge badge-warning text-dark px-2 py-1">កំពុងរង់ចាំ</span>
                        @endif
                    </div>
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($order->results as $res)
                                <tr>
                                    <td class="pl-3">{{ $res->labTest->test_name ?? 'Test #' . $res->test_id }}</td>
                                    <td>
                                        @if($res->result_value && $res->result_value !== 'Pending')
                                            <span class="font-weight-bold text-success">{{ $res->result_value }}</span>
                                            {{ $res->labTest->unit ?? '' }}
                                        @else
                                            <span class="badge badge-warning text-dark">កំពុងរង់ចាំ</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $res->normal_range }}</td>
                                    <td class="text-right pr-3">
                                        <a href="{{ route('lab-results.show', $res->result_id) }}"
                                            class="btn btn-sm btn-light text-info rounded-circle"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-muted small mb-0">គ្មានការកម្មង់ពិនិត្យមន្ទីរពិសោធន៍</p>
            @endforelse
        </div>
    </div>
</div>

@if($canModify)
    {{-- ============ DELETE MODAL ============ --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('medical-records.destroy', $record->record_id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header modal-header-custom">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-trash-alt text-danger mr-2"></i> លុប #MR-{{ $record->record_id }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        តើអ្នកពិតជាចង់លុប Medical Record នេះមែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។
                        <small class="d-block text-muted mt-1">Delete this medical record? This cannot be undone.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                        <button type="submit" class="btn btn-danger px-4"><i class="fas fa-trash-alt mr-1"></i> លុប</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@stop
