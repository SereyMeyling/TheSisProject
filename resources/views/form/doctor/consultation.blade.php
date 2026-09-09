@extends('adminlte::page')

@section('title', 'បន្ទប់ពិនិត្យជំងឺ (Doctor Consultation)')

@section('content')

<style>
    .consult-container {
        font-family: 'Inter', 'Kantumruuy Pro', sans-serif;
    }
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .modal-header-custom {
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: #ffffff;
    }
</style>

<div class="consult-container">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h2 class="font-weight-bold text-dark mb-1">
                <i class="fas fa-stethoscope text-primary mr-2"></i> បន្ទប់ពិនិត្យ និងព្យាបាលអ្នកជំងឺ (Doctor Consultation)
            </h2>
            <small class="text-muted">កត់ត្រារោគវិនិច្ឆ័យ វេជ្ជបញ្ជា និងបញ្ជូនអ្នកជំងឺទៅកាន់គោលដៅបន្ទាប់</small>
        </div>

        <div>
            <a href="{{ route('doctor.index') }}" class="btn btn-outline-secondary font-weight-bold" style="border-radius: 10px;">
                <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ក្រោយ
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        {{-- Patient Info & Vitals Side Panel --}}
        <div class="col-lg-4 mb-4">
            <div class="card-modern mb-3">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-user-circle text-primary mr-2"></i> ព័ត៌មានអ្នកជំងឺ (Patient Info)</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">កូដអ្នកជំងឺ:</span>
                        <span class="badge badge-light border font-weight-bold text-dark">{{ $record->patient->patient_code ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ឈ្មោះពេញ:</span>
                        <strong class="text-dark">{{ $record->patient->full_name ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ភេទ / អាយុ:</span>
                        <span>{{ ($record->patient->sex ?? '') == 'Male' ? 'ប្រុស' : 'ស្រី' }} | {{ $record->patient->date_of_birth ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">លេខទូរសព្ទ:</span>
                        <span>{{ $record->patient->phone ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">អាសយដ្ឋាន:</span>
                        <span class="text-right text-dark" style="max-width: 180px;">{{ $record->patient->address ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="card-modern mb-3">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="mb-0 font-weight-bold text-primary"><i class="fas fa-heartbeat mr-2"></i> សញ្ញាជីវិត (Vital Signs)</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row text-center">
                        <div class="col-6 border-right mb-3">
                            <small class="text-muted d-block mb-1">សម្ពាធឈាម (BP)</small>
                            <span class="font-weight-bold text-dark">120/80 mmHg</span>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block mb-1">កំដៅខ្លួន (Temp)</small>
                            <span class="font-weight-bold text-danger">37.2 °C</span>
                        </div>
                        <div class="col-6 border-right">
                            <small class="text-muted d-block mb-1">ចង្វាក់បេះដូង</small>
                            <span class="font-weight-bold text-success">75 bpm</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block mb-1">ទម្ងន់ (Weight)</small>
                            <span class="font-weight-bold text-info">65 Kg</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-history text-secondary mr-2"></i> ប្រវត្តិព្យាបាលចាស់ៗ</h6>
                </div>
                <div class="card-body p-3" style="max-height: 220px; overflow-y: auto;">
                    @forelse($historyRecords as $hist)
                        <div class="border-bottom mb-2 pb-2">
                            <small class="text-muted"><i class="far fa-clock mr-1"></i> {{ $hist->visit_date }}</small>
                            <p class="mb-0 text-dark small"><strong>រោគវិនិច្ឆ័យ:</strong> {{ $hist->diagnosis }}</p>
                        </div>
                    @empty
                        <p class="text-muted small text-center my-2">គ្មានប្រវត្តិព្យាបាលចាស់ទេ។</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Consultation Form --}}
        <div class="col-lg-8 mb-4">
            <div class="card-modern">
                <div class="card-header modal-header-custom py-3">
                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-notes-medical mr-2"></i> កត់ត្រាវេជ្ជសាស្ត្រ និងទិសដៅបន្ត</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('doctor.update', $record->record_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">រោគវិនិច្ឆ័យ (Diagnosis) <span class="text-danger">*</span></label>
                            <textarea name="diagnosis" class="form-control" rows="3" placeholder="បញ្ចូលរោគវិនិច្ឆ័យរបស់អ្នកជំងឺ..." style="border-radius: 10px;" required>{{ old('diagnosis', $record->diagnosis) }}</textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark">ចំណាំទូទៅ (Notes)</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="ចំណាំបន្ថែម..." style="border-radius: 10px;">{{ old('notes', $record->notes) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark">វេជ្ជបញ្ជា / ឱសថការី (Prescription Notes)</label>
                                    <textarea name="prescription_notes" class="form-control" rows="3" placeholder="ឈ្មោះថ្នាំ និងកម្រិតប្រើប្រាស់..." style="border-radius: 10px;">{{ old('prescription_notes', $record->prescription_notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border p-3 mb-4" style="border-radius: 12px;">
                            <span class="font-weight-bold text-primary"><i class="fas fa-share-square mr-1"></i> ជ្រើសរើសទិសដៅបន្តរបស់អ្នកជំងឺ៖</span>
                            <p class="text-muted small mb-0 mt-1">សូមជ្រើសរើសប៊ូតុងណាមួយខាងក្រោមដើម្បីរក្សាទុក និងបញ្ជូនអ្នកជំងឺទៅកាន់គោលដៅបន្ទាប់។</p>
                        </div>

                        <div class="d-flex justify-content-between flex-wrap" style="gap: 12px;">
                            <button type="submit" name="status_destination" value="admit" class="btn btn-outline-primary font-weight-bold flex-fill py-2" style="border-radius: 10px;">
                                <i class="fas fa-bed mr-1"></i> សម្រាកព្យាបាល (Admit)
                            </button>

                            <button type="submit" name="status_destination" value="pharmacy" class="btn btn-outline-primary font-weight-bold flex-fill py-2" style="border-radius: 10px;">
                                <i class="fas fa-pills mr-1"></i> ទៅកន្លែងចេញថ្នាំ (Pharmacy)
                            </button>

                            <button type="submit" name="status_destination" value="done" class="btn btn-primary font-weight-bold flex-fill py-2" style="border-radius: 10px;">
                                <i class="fas fa-check-circle mr-1"></i> បញ្ចប់ការពិនិត្យ (Finish)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@stop
