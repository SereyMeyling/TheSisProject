@extends('adminlte::page')

@section('title', 'បន្ទប់ពិនិត្យជំងឺ (Doctor Consultation)')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="font-weight-bold text-dark"><i class="fas fa-stethoscope mr-2 text-success"></i> បន្ទប់ពិនិត្យ និងព្យាបាលអ្នកជំងឺ</h4>
        <a href="{{ route('doctor.index') }}" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
        </a>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-3 rounded-lg">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-user mr-1"></i> ព័ត៌មានអ្នកជំងឺ (Patient Info)</h6>
            </div>
            <div class="card-body py-2">
                <p class="mb-1 text-sm"><strong>កូដ:</strong> <span class="badge badge-success">{{ $record->patient->patient_code }}</span></p>
                <p class="mb-1 text-sm"><strong>ឈ្មោះ:</strong> {{ $record->patient->full_name }}</p>
                <p class="mb-1 text-sm"><strong>ភេទ / អាយុ:</strong> {{ $record->patient->sex == 'Male' ? 'ប្រុស' : 'ស្រី' }} | {{ $record->patient->date_of_birth }}</p>
                <p class="mb-1 text-sm"><strong>ទូរសព្ទ:</strong> {{ $record->patient->phone ?? '-' }}</p>
                <p class="mb-0 text-sm"><strong>អាសយដ្ឋាន:</strong> {{ $record->patient->address ?? '-' }}</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3 rounded-lg border-left border-primary">
            <div class="card-header bg-light">
                <h6 class="mb-0 font-weight-bold text-primary"><i class="fas fa-heartbeat mr-1"></i> សញ្ញាជីវិត (Vital Signs)</h6>
            </div>
            <div class="card-body p-2">
                <div class="row text-center">
                    <div class="col-6 border-right mb-2">
                        <small class="text-muted d-block">សម្ពាធឈាម (BP)</small>
                        <span class="font-weight-bold text-dark">120/80 mmHg</span>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted d-block">កំដៅខ្លួន (Temp)</small>
                        <span class="font-weight-bold text-danger">37.2 °C</span>
                    </div>
                    <div class="col-6 border-right">
                        <small class="text-muted d-block">ចង្វាក់បេះដូង</small>
                        <span class="font-weight-bold text-success">75 bpm</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">ទម្ងន់ (Weight)</small>
                        <span class="font-weight-bold text-info">65 Kg</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-lg">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-history mr-1"></i> ប្រវត្តិព្យាបាលចាស់ៗ</h6>
            </div>
            <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                @forelse($historyRecords as $hist)
                    <div class="border-bottom mb-2 pb-2">
                        <small class="text-muted"><i class="far fa-clock"></i> ថ្ងៃទី: {{ $hist->visit_date }}</small>
                        <p class="mb-0 text-dark small"><strong>រោគវិនិច្ឆ័យ:</strong> {{ $hist->diagnosis }}</p>
                    </div>
                @empty
                    <p class="text-muted small text-center my-2">គ្មានប្រវត្តិព្យាបាលចាស់ទេ។</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-lg">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-notes-medical mr-1"></i> កត់ត្រាវេជ្ជសាស្ត្រ និងទិសដៅបន្ត</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('doctor.update', $record->record_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-bold text-dark">រោគវិនិច្ឆ័យ (Diagnosis) <span class="text-danger">*</span></label>
                        <textarea name="diagnosis" class="form-control" rows="3" placeholder="បញ្ចូលរោគវិនិច្ឆ័យរបស់អ្នកជំងឺ..." required>{{ old('diagnosis', $record->diagnosis) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">ចំណាំទូទៅ (Notes)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ចំណាំបន្ថែម...">{{ old('notes', $record->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">វេជ្ជបញ្ជា / ឱសថការី (Prescription Notes)</label>
                                <textarea name="prescription_notes" class="form-control" rows="2" placeholder="ឈ្មោះថ្នាំ និងកម្រិតប្រើប្រាស់...">{{ old('prescription_notes', $record->prescription_notes) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="alert alert-light border p-2 mb-3">
                        <span class="font-weight-bold text-success"><i class="fas fa-share-square mr-1"></i> ជ្រើសរើសទិសដៅបន្តរបស់អ្នកជំងឺ៖</span>
                        <p class="text-muted small mb-0">សូមចុចប៊ូតុងណាមួយខាងក្រោមដើម្បីរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅកាន់គោលដៅបន្ទាប់។</p>
                    </div>

                    <div class="d-flex justify-content-between flex-wrap" style="gap: 10px;">
                        <button type="submit" name="status_destination" value="admit" class="btn btn-success font-weight-bold shadow-sm flex-fill py-2 text-white">
                            <i class="fas fa-bed mr-1"></i> សម្រាកព្យាបាល (Admit)
                        </button>

                        <button type="submit" name="status_destination" value="pharmacy" class="btn btn-success font-weight-bold shadow-sm flex-fill py-2">
                            <i class="fas fa-pills mr-1"></i> ទៅកន្លែងចេញថ្នាំ
                        </button>

                        <button type="submit" name="status_destination" value="done" class="btn btn-success font-weight-bold shadow-sm flex-fill py-2">
                            <i class="fas fa-check-circle mr-1"></i> បញ្ចប់ការពិនិត្យ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
