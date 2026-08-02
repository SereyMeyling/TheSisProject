@extends('adminlte::page')

@section('title', 'កែប្រែព័ត៌មានវេជ្ជសាស្ត្រ')

@section('content_header')
    <h1>កែប្រែព័ត៌មានវេជ្ជសាស្ត្រ</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('medical-records.update', $medicalRecord->record_id ?? $medicalRecord->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>ជ្រើសរើសអ្នកជំងឺ</label>
                <select name="patient_id" class="form-control" required>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->patient_id }}" {{ $medicalRecord->patient_id == $patient->patient_id ? 'selected' : '' }}>
                            {{ $patient->full_name }} ({{ $patient->patient_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>រោគវិនិច្ឆ័យ (Diagnosis)</label>
                <textarea name="diagnosis" class="form-control" rows="3">{{ $medicalRecord->diagnosis }}</textarea>
            </div>

            <div class="form-group">
                <label>កំណត់ចំណាំ / វេជ្ជបញ្ជា (Notes)</label>
                <textarea name="notes" class="form-control" rows="3">{{ $medicalRecord->notes }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label>សម្ពាធឈាម (BP Systolic)</label>
                    <input type="number" name="bp_systolic" class="form-control" value="{{ $medicalRecord->bp_systolic }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>សម្ពាធឈាម (BP Diastolic)</label>
                    <input type="number" name="bp_diastolic" class="form-control" value="{{ $medicalRecord->bp_diastolic }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">រក្សាទុកការកែប្រែ</button>
            <a href="{{ route('medical-records.index') }}" class="btn btn-secondary">បោះបង់</a>
        </form>
    </div>
</div>
@stop
