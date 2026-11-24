@extends('adminlte::page')

@section('title', 'លទ្ធផលពិនិត្យមន្ទីរពិសោធន៍ (Lab Result)')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
        <i class="fas fa-vial text-success mr-2"></i> លទ្ធផលពិនិត្យ #RES-{{ $labResult->result_id }}
    </h1>
    <a href="{{ route('lab.index') }}" class="btn btn-secondary px-3 rounded-pill shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
    </a>
</div>
@stop

@section('content')
@php
    $order = $labResult->labOrder;
    $record = optional($order)->medicalRecord;
    $patient = optional($record)->patient;
    $doctor = optional($record)->doctor;
    $test = $labResult->labTest;
    $isPending = $labResult->result_value === null || $labResult->result_value === 'Pending';
@endphp

<div class="container-fluid">
    <div class="card border-0 shadow-sm rounded-lg">
        <div class="card-body p-4">
            <div class="row border-bottom pb-3 mb-3">
                <div class="col-md-6">
                    <h5 class="font-weight-bold text-success">{{ $patient->full_name ?? 'N/A' }}</h5>
                    <p class="text-muted mb-0">លេខកូដអ្នកជំងឺ: {{ $patient->patient_code ?? '-' }}</p>
                    <p class="text-muted mb-0">ភេទ: {{ $patient->sex ?? '-' }} | អាយុ: {{ $patient->age ?? '-' }} ឆ្នាំ</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-1"><strong>លេខការកម្មង់:</strong>
                        {{ $order ? '#LAB-' . $order->lab_order_id : '-' }}</p>
                    <p class="mb-1"><strong>កាលបរិច្ឆេទកម្មង់:</strong>
                        {{ $order && $order->order_date ? $order->order_date->format('d/m/Y H:i') : '-' }}</p>
                    <p class="mb-1"><strong>គ្រូពេទ្យ:</strong> {{ $doctor->name ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>ស្ថានភាព:</strong>
                        @if (optional($order)->status === 'completed')
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>បានបញ្ចប់</span>
                        @else
                            <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>កំពុងរង់ចាំ</span>
                        @endif
                    </p>
                </div>
            </div>

            <h6 class="font-weight-bold text-dark mb-3">
                <i class="fas fa-microscope text-primary mr-2"></i> លទ្ធផលតេស្ត (Test Result)
            </h6>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-3">
                    <thead class="bg-light">
                        <tr class="text-secondary small">
                            <th>តេស្ត (Test)</th>
                            <th>លទ្ធផល (Result)</th>
                            <th>ឯកតា (Unit)</th>
                            <th>កម្រិតធម្មតា (Normal range)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-weight-bold">
                                {{ $test->test_name ?? 'Test #' . $labResult->test_id }}
                                @if (!empty($test->test_code))
                                    <small class="text-muted d-block">{{ $test->test_code }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($isPending)
                                    <span class="badge badge-warning text-dark px-2 py-1">កំពុងរង់ចាំ</span>
                                @else
                                    <span class="font-weight-bold text-success">{{ $labResult->result_value }}</span>
                                @endif
                            </td>
                            <td>{{ $test->unit ?? '-' }}</td>
                            <td>{{ $labResult->normal_range ?? ($test->normal_range ?? '-') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <h6 class="font-weight-bold text-dark">ចំណាំ (Remark)</h6>
                <div class="p-3 bg-light rounded border" style="white-space: pre-line;">{{ $labResult->remark ?: 'គ្មានចំណាំ' }}</div>
            </div>

            <p class="text-muted small mt-3 mb-0">
                កែប្រែចុងក្រោយ: {{ $labResult->updated_at ? $labResult->updated_at->format('d/m/Y H:i') : '-' }}
            </p>
        </div>
    </div>
</div>
@stop
