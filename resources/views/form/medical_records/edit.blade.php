@extends('adminlte::page')

@section('title', 'កែប្រែ Medical Record #MR-' . $medicalRecord->record_id)

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
        <i class="fas fa-edit text-primary mr-2"></i> កែប្រែ #MR-{{ $medicalRecord->record_id }}
    </h1>
    <a href="{{ route('medical-records.show', $medicalRecord->record_id) }}"
        class="btn btn-secondary px-3 rounded-pill shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
    </a>
</div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-lg">
        <form method="POST" action="{{ route('medical-records.update', $medicalRecord->record_id) }}">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label class="small font-weight-bold text-secondary">អ្នកជំងឺ</label>
                        {{-- The patient of a record cannot be changed. --}}
                        <input type="text" class="form-control bg-light" readonly
                            value="{{ $medicalRecord->patient->full_name ?? 'N/A' }} ({{ $medicalRecord->patient->patient_code ?? '-' }})">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="small font-weight-bold text-secondary">គ្រូពេទ្យពិនិត្យ (Doctor)</label>
                        <input type="text" class="form-control bg-light" readonly
                            value="{{ $medicalRecord->doctor->name ?? 'N/A' }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="small font-weight-bold text-secondary">កាលបរិច្ឆេទពិនិត្យ</label>
                        <input type="text" class="form-control bg-light" readonly
                            value="{{ $medicalRecord->visit_date ? $medicalRecord->visit_date->format('d/m/Y H:i') : '-' }}">
                    </div>
                </div>

                <h6 class="font-weight-bold text-success mb-3">
                    <i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)
                </h6>
                <div class="row">
                    @foreach([['bp_systolic', 'BP Systolic (mmHg)', '0.01'], ['bp_diastolic', 'BP Diastolic (mmHg)', '0.01'], ['heart_rate', 'Heart Rate (bpm)', '1'], ['respiratory_rate', 'Respiratory Rate', '1'], ['temperature', 'Temperature (°C)', '0.1'], ['spo2', 'SpO2 (%)', '0.1'], ['weight', 'Weight (kg)', '0.1']] as [$name, $label, $step])
                        <div class="col-md-3 form-group">
                            <label class="small font-weight-bold text-secondary">{{ $label }}</label>
                            <input type="number" name="{{ $name }}" step="{{ $step }}"
                                class="form-control @error($name) is-invalid @enderror"
                                value="{{ old($name, $medicalRecord->$name) }}">
                            @error($name)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold text-secondary">ការវិនិច្ឆ័យរោគ (Diagnosis)</label>
                    <textarea name="diagnosis" maxlength="255" rows="3"
                        class="form-control @error('diagnosis') is-invalid @enderror">{{ old('diagnosis', $medicalRecord->diagnosis) }}</textarea>
                    @error('diagnosis')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-secondary">ចំណាំបន្ថែម (Notes / Symptoms)</label>
                    <textarea name="notes" maxlength="255" rows="2"
                        class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $medicalRecord->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card-footer bg-white text-right">
                <a href="{{ route('medical-records.show', $medicalRecord->record_id) }}" class="btn btn-secondary">បោះបង់</a>
                <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                    <i class="fas fa-save mr-1"></i> រក្សាទុកការកែប្រែ
                </button>
            </div>
        </form>
    </div>
</div>
@stop
