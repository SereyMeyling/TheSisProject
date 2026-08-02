@extends('adminlte::page')

@section('title', 'បង្កើត Medical Record')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
            <i class="fas fa-notes-medical text-success mr-2"></i> បញ្ចូលកំណត់ត្រាពិនិត្យជំងឺ (Medical Record)
        </h1>
        <a href="{{ route('medical-records.index') }}" class="btn btn-outline-secondary px-3 rounded-pill shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ទៅបញ្ជី
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form method="POST" action="{{ route('medical-records.store') }}">
        @csrf
        <div class="card border-0 shadow-sm rounded-lg">
            <div class="card-body bg-light p-4">

                <div class="row bg-white p-3 rounded shadow-sm mb-4">
                    <div class="col-md-4 form-group">
                        <label class="small font-weight-bold text-secondary">ជ្រើសរើសអ្នកជំងឺ <span class="text-danger">*</span></label>
                        <select name="patient_id" class="form-control" required>
                            <option value="" disabled selected>-- ជ្រើសរើសអ្នកជំងឺ --</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->patient_id }}" {{ (isset($selectedPatientId) && $selectedPatientId == $patient->patient_id) ? 'selected' : '' }}>
                                    {{ $patient->patient_code ?? 'ID: '.$patient->patient_id }} - {{ $patient->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="small font-weight-bold text-secondary">គ្រូពេទ្យពិនិត្យ (Doctor / Employee)</label>
                        <select name="employee_id" class="form-control">
                            <option value="" selected>-- ជ្រើសរើសគ្រូពេទ្យ --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->employee_id }}">{{ $doc->first_name }} {{ $doc->last_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label class="small font-weight-bold text-secondary">កាលបរិច្ឆេទពិនិត្យ <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="visit_date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                    </div>
                </div>

                <div class="bg-white p-3 rounded shadow-sm mb-4">
                    <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)</h6>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">BP Systolic (mmHg)</label>
                            <input type="number" name="bp_systolic" class="form-control" placeholder="120" value="{{ old('bp_systolic') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">BP Diastolic (mmHg)</label>
                            <input type="number" name="bp_diastolic" class="form-control" placeholder="80" value="{{ old('bp_diastolic') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" class="form-control" placeholder="72" value="{{ old('heart_rate') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">Respiratory Rate</label>
                            <input type="number" name="respiratory_rate" class="form-control" placeholder="18" value="{{ old('respiratory_rate') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-secondary">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="36.5" value="{{ old('temperature') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-secondary">SpO2 (%)</label>
                            <input type="number" step="0.1" name="spo2" class="form-control" placeholder="98" value="{{ old('spo2') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="small font-weight-bold text-secondary">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control" placeholder="65" value="{{ old('weight') }}">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-3 rounded shadow-sm mb-4">
                    <div class="form-group">
                        <label class="small font-weight-bold text-secondary">ការវិនិច្ឆ័យរោគ (Diagnosis)</label>
                        <textarea name="diagnosis" class="form-control" rows="3" placeholder="បញ្ចូលលទ្ធផលនៃការវិនិច្ឆ័យ...">{{ old('diagnosis') }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-secondary">ចំណាំបន្ថែម (Notes / Symptoms)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="បញ្ចូលរោគសញ្ញា ឬការកត់សម្គាល់បន្ថែម...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-5 font-weight-bold shadow-sm" style="background-color: #00695c;">
                        <i class="fas fa-save mr-2"></i> រក្សាទុក Medical Record
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@stop
