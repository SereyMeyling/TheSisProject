@extends('adminlte::page')

@section('title', 'ព័ត៌មានលម្អិតអ្នកជំងឺ')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="m-0 text-dark font-weight-bold">ព័ត៌មានលម្អិតអ្នកជំងឺ</h4>
        <a href="{{ route('patients.index') }}" class="btn btn-secondary px-3 rounded-pill font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
        </a>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-lg">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-3">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h5 class="font-weight-bold">{{ $patient->full_name }}</h5>
                <p class="text-muted mb-1"><span class="badge badge-success">{{ $patient->patient_code }}</span></p>
                <hr>
                <ul class="list-group list-group-flush text-left">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">ភេទ:</span>
                        <span class="font-weight-bold">{{ $patient->sex == 'Male' ? 'ប្រុស' : 'ស្រី' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">ថ្ងៃកំណើត:</span>
                        <span class="font-weight-bold">{{ $patient->date_of_birth }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">លេខទូរសព្ទ:</span>
                        <span class="font-weight-bold">{{ $patient->phone ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">អត្តសញ្ញាណប័ណ្ណ:</span>
                        <span class="font-weight-bold">{{ $patient->id_card ?? '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-lg">
            <div class="card-header bg-white">
                <h6 class="font-weight-bold mb-0 text-success"><i class="fas fa-history mr-1"></i> ប្រវត្តិវេជ្ជសាស្ត្ររបស់អ្នកជំងឺ</h6>
            </div>
            <div class="card-body">
                @if(isset($medicalRecords) && $medicalRecords->count() > 0)
                    <ul class="timeline">
                        @foreach($medicalRecords as $record)
                            <div class="card border mb-2 shadow-sm">
                                <div class="card-body p-3">
                                    <span class="badge badge-info float-right">{{ $record->visit_date }}</span>
                                    <h6 class="font-weight-bold text-dark">រោគវិនិច្ឆ័យ: {{ $record->diagnosis ?? 'គ្មាន' }}</h6>
                                    <p class="mb-1 text-muted small">ចំណាំ: {{ $record->notes ?? 'គ្មាន' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center py-4">មិនទាន់មានប្រវត្តិវេជ្ជសាស្ត្រសម្រាប់អ្នកជំងឺរូបនេះឡើយ។</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
