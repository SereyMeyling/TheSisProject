@extends('adminlte::page')

@section('title', 'ចុះឈ្មោះអ្នកជំងឺថ្មី')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
            <i class="fas fa-user-plus text-success mr-2"></i> ចុះឈ្មោះអ្នកជំងឺថ្មី
        </h1>
        <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary px-3 rounded-pill shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> ត្រឡប់ទៅបញ្ជី
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('patients.store') }}">
                    @csrf
                    <div class="card border-0 shadow-sm rounded-lg overflow-hidden">
                        <div
                            class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title font-weight-bold m-0 text-success">ព័ត៌មានអ្នកជំងឺ</h5>
                            <span class="badge badge-primary px-3 py-1">NEW RECORD</span>
                        </div>

                        <div class="card-body bg-light p-4">
                            <div class="row bg-white p-3 rounded shadow-sm mb-4">
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold text-secondary">ឈ្មោះពេញ (Full Name) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control border-light-gray"
                                        placeholder="បញ្ចូលឈ្មោះពេញ..." required value="{{ old('full_name') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold text-secondary">លេខអត្តសញ្ញាណប័ណ្ណ / ID
                                        Card</label>
                                    <input type="text" name="id_card" class="form-control border-light-gray"
                                        placeholder="បញ្ចូលលេខអត្តសញ្ញាណប័ណ្ណ..." value="{{ old('id_card') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold text-secondary">ថ្ងៃខែឆ្នាំកំណើត <span
                                            class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-control border-light-gray"
                                        required value="{{ old('date_of_birth') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small font-weight-bold text-secondary">ភេទ <span
                                            class="text-danger">*</span></label>
                                    <select name="sex" class="form-control border-light-gray" required>
                                        <option value="" disabled selected>ជ្រើសរើស</option>
                                        <option value="male" {{ old('sex') == 'male' ? 'selected' : '' }}>ប្រុស</option>
                                        <option value="female" {{ old('sex') == 'female' ? 'selected' : '' }}>ស្រី</option>
                                        <option value="other" {{ old('sex') == 'other' ? 'selected' : '' }}>ផ្សេងៗ</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label class="small font-weight-bold text-secondary">លេខទំនាក់ទំនង</label>
                                    <input type="text" name="phone" class="form-control border-light-gray"
                                        placeholder="+855 000-0000" value="{{ old('phone') }}">
                                </div>
                                <div class="col-md-6 form-group mb-0">
                                    <label class="small font-weight-bold text-secondary">អាសយដ្ឋាន</label>
                                    <input type="text" name="address" class="form-control border-light-gray"
                                        placeholder="បញ្ចូលអាសយដ្ឋានបច្ចុប្បន្ន..." value="{{ old('address') }}">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light border px-4 mr-2">Clear Form</button>
                                <button type="submit" class="btn btn-success px-5 font-weight-bold shadow-sm"
                                    style="background-color: #00695c; border-color: #00695c;">
                                    <i class="fas fa-save mr-2"></i> ចុះឈ្មោះអ្នកជំងឺ
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-lg overflow-hidden">
                    <div class="card-header bg-success py-3 d-flex justify-content-between align-items-center"
                        style="background-color: #00695c !important;">
                        <h6 class="font-weight-bold mb-0 text-white"><i class="fas fa-clock mr-2"></i> ជួររង់ចាំផ្ទាល់ (Live
                            Queue)</h6>
                        <span class="badge badge-light text-success font-weight-bold">{{ $waitingPatients->count() }}
                            នាក់</span>
                    </div>
                    <div class="card-body p-3 bg-light" style="max-height: 520px; overflow-y: auto;">

                        @forelse($waitingPatients as $record)
                            <div class="card border-0 shadow-sm mb-2 rounded-lg">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="font-weight-bold mb-1 text-dark">
                                                {{ $record->patient->full_name ?? 'N/A' }}</h6>
                                            <small class="text-muted"><i class="fas fa-id-badge mr-1"></i>
                                                {{ $record->patient->patient_code ?? 'N/A' }}</small>
                                        </div>
                                        <span
                                            class="badge badge-success px-2 py-1">{{ $record->created_at ? $record->created_at->diffForHumans() : '' }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-users-slash fa-3x text-secondary mb-3"></i>
                                <p class="text-muted small">មិនទាន់មានអ្នកជំងឺរង់ចាំឡើយ</p>
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .border-light-gray {
            border: 1px solid #e9ecef !important;
        }

        .card-title {
            font-size: 1rem;
        }

        input::placeholder {
            font-size: 0.85rem;
            color: #adb5bd;
        }
    </style>
@stop
