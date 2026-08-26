@extends('adminlte::page')

@section('title', 'ព័ត៌មានលម្អិត Medical Record')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
            <i class="fas fa-file-invoice text-success mr-2"></i> ព័ត៌មានលម្អិត #MR-{{ $record->record_id }}
        </h1>
        <a href="{{ route('medical-records.index') }}" class="btn btn-outline-secondary px-3 rounded-pill shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ទៅបញ្ជី
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="card border-0 shadow-sm rounded-lg p-4">
        <div class="row border-bottom pb-3 mb-3">
            <div class="col-md-6">
                <h5 class="font-weight-bold text-success">{{ $record->patient->full_name ?? 'N/A' }}</h5>
                <p class="text-muted mb-0">លេខកូដអ្នកជំងឺ: {{ $record->patient->patient_code ?? 'N/A' }}</p>
                <p class="text-muted mb-0">ភេទ: {{ $record->patient->sex ?? '-' }} | អាយុ: {{ $record->patient->age ?? '-' }} ឆ្នាំ</p>
            </div>
            <div class="col-md-6 text-md-right">
                <p class="mb-1"><strong>កាលបរិច្ឆេទពិនិត្យ:</strong> {{ $record->visit_date ? $record->visit_date->format('d/m/Y H:i A') : '-' }}</p>
                <p class="mb-0"><strong>គ្រូពេទ្យទទួលខុសត្រូវ:</strong> {{ $record->doctor ? ($record->doctor->first_name . ' ' . $record->doctor->last_name) : 'N/A' }}</p>
            </div>
        </div>

        <h6 class="font-weight-bold text-dark mt-2 mb-3"><i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)</h6>
        <div class="row text-center mb-4">
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">BP</small>
                    <strong>{{ $record->bp_systolic ?? '-' }}/{{ $record->bp_diastolic ?? '-' }}</strong> <small>mmHg</small>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">Heart Rate</small>
                    <strong>{{ $record->heart_rate ?? '-' }}</strong> <small>bpm</small>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">Resp. Rate</small>
                    <strong>{{ $record->respiratory_rate ?? '-' }}</strong>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">Temp</small>
                    <strong>{{ $record->temperature ?? '-' }}</strong> <small>°C</small>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">SpO2</small>
                    <strong>{{ $record->spo2 ?? '-' }}</strong> <small>%</small>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="p-2 bg-light rounded border">
                    <small class="text-muted d-block">Weight</small>
                    <strong>{{ $record->weight ?? '-' }}</strong> <small>kg</small>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h6 class="font-weight-bold text-dark">ការវិនិច្ឆ័យរោគ (Diagnosis)</h6>
            <div class="p-3 bg-light rounded border">
                {{ $record->diagnosis ?? 'គ្មានការវិនិច្ឆ័យ' }}
            </div>
        </div>

        <div>
            <h6 class="font-weight-bold text-dark">ចំណាំបន្ថែម (Notes)</h6>
            <div class="p-3 bg-light rounded border">
                {{ $record->notes ?? 'គ្មានចំណាំ' }}
            </div>
        </div>
    </div>
</div>
@stop
