@extends('adminlte::page')

@section('title', 'បន្ទប់ពិនិត្យ និងព្យាបាលជំងឺ (Doctor Consultation Queue)')

@section('content')

<style>
    .doctor-container {
        font-family: 'Inter', 'Kantumruuy Pro', sans-serif;
    }
    .stat-card-doc {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }
    .stat-card-doc:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .bg-blue-gradient   { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; }
    .bg-emerald-gradient{ background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: white; }
    .bg-purple-gradient { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; }

    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .toolbar-filters {
        padding: 16px 20px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
    }
    .search-box {
        position: relative;
        flex: 1;
        max-width: 350px;
    }
    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .search-box input {
        padding-left: 38px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="doctor-container">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h2 class="font-weight-bold text-dark mb-1">
                <i class="fas fa-user-md text-primary mr-2"></i> បន្ទប់ពិនិត្យ និងព្យាបាលជំងឺ (Doctor Consultation Queue)
            </h2>
            <small class="text-muted">បញ្ជីឈ្មោះអ្នកជំងឺរង់ចាំជួបពិគ្រោះ និងពិនិត្យព្យាបាលជំងឺ</small>
        </div>

        <div>
            <span class="badge badge-light border shadow-sm px-3 py-2 text-dark font-weight-bold" style="border-radius: 10px; font-size: 14px;">
                <i class="far fa-calendar-alt text-primary mr-1"></i> ថ្ងៃនេះ: {{ date('d-m-Y') }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px; background: #ecfdf5; color: #065f46;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
        </div>
    @endif

    {{-- Stats Cards Grid --}}
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="stat-card-doc">
                <div class="stat-icon bg-blue-gradient">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <small class="text-muted font-weight-bold d-block">អ្នកជំងឺរង់ចាំ (Waiting Patients)</small>
                    <h3 class="m-0 font-weight-bold text-primary">{{ $totalWaiting ?? $waitingPatients->total() }}</h3>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="stat-card-doc">
                <div class="stat-icon bg-emerald-gradient">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div>
                    <small class="text-muted font-weight-bold d-block">ពិនិត្យថ្ងៃនេះ (Visits Today)</small>
                    <h3 class="m-0 font-weight-bold text-success">{{ $totalToday ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="stat-card-doc">
                <div class="stat-icon bg-purple-gradient">
                    <i class="fas fa-hospital-alt"></i>
                </div>
                <div>
                    <small class="text-muted font-weight-bold d-block">ស្ថានភាពបន្ទប់ (Clinic Status)</small>
                    <h3 class="m-0 font-weight-bold text-purple" style="font-size: 1.25rem;"><span class="badge badge-success px-3 py-1">ដំណើរការធម្មតា</span></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Patient Queue Table --}}
        <div class="col-lg-8 mb-4">
            <div class="card-modern">
                <div class="toolbar-filters d-flex justify-content-between align-items-center flex-wrap">
                    <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-procedures text-primary mr-2"></i> បញ្ជីអ្នកជំងឺរង់ចាំជួបគ្រូពេទ្យ</h6>
                    <form action="{{ route('doctor.index') }}" method="GET" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="ស្វែងរកឈ្មោះ ឬកូដអ្នកជំងឺ..." value="{{ request('search') }}">
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>កូដអ្នកជំងឺ</th>
                                    <th>ឈ្មោះអ្នកជំងឺ</th>
                                    <th>ភេទ</th>
                                    <th>កាលបរិច្ឆេទមកដល់</th>
                                    <th>រោគវិនិច្ឆ័យបឋម</th>
                                    <th class="text-right">សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingPatients as $record)
                                    <tr>
                                        <td>
                                            <span class="badge badge-light border font-weight-bold text-dark">{{ $record->patient->patient_code ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-primary">
                                                <i class="fas fa-user-circle text-muted mr-1"></i>
                                                {{ $record->patient->full_name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if(($record->patient->sex ?? '') === 'Male')
                                                <span class="badge badge-primary px-2 py-1"><i class="fas fa-mars mr-1"></i>ប្រុស</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-venus mr-1"></i>ស្រី</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted font-weight-bold">
                                                <i class="far fa-clock text-info mr-1"></i>
                                                {{ $record->visit_date ? \Carbon\Carbon::parse($record->visit_date)->format('d/m/Y H:i') : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="text-dark">{{ Str::limit($record->diagnosis ?? 'មិនទាន់មាន', 25) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end">
                                                <a href="{{ route('doctor.consultation', $record->record_id) }}" class="btn btn-sm btn-primary font-weight-bold" style="border-radius: 8px;">
                                                    <i class="fas fa-stethoscope mr-1"></i> ពិនិត្យជំងឺ
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-user-check fa-3x mb-3 text-muted opacity-50"></i>
                                            <p class="font-weight-bold mb-1">គ្មានអ្នកជំងឺរង់ចាំពិនិត្យទេ</p>
                                            <small>បញ្ជីរង់ចាំទាំងអស់ត្រូវពិនិត្យរួចរាល់</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
                    {!! $waitingPatients->links('pagination::bootstrap-4') !!}
                </div>
            </div>
        </div>

        {{-- Next Queue Side Panel --}}
        <div class="col-lg-4">
            <div class="card-modern mb-3">
                <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-list-ol text-warning mr-2"></i> អ្នកជំងឺរង់ចាំបន្ទាប់</h6>
                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">{{ $waitingPatients->count() }} នាក់</span>
                </div>
                <div class="card-body p-3" style="max-height: 440px; overflow-y: auto;">
                    @forelse($waitingPatients as $index => $nextPatient)
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded border bg-light">
                            <div>
                                <span class="badge badge-primary mr-1" style="border-radius: 50%;">#{{ $index + 1 }}</span>
                                <span class="font-weight-bold text-dark">{{ $nextPatient->patient->full_name ?? 'N/A' }}</span>
                                <br>
                                <small class="text-muted ml-4"><i class="far fa-id-card mr-1"></i>{{ $nextPatient->patient->patient_code ?? '' }}</small>
                            </div>
                            <div>
                                <a href="{{ route('doctor.consultation', $nextPatient->record_id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" title="ចូលពិនិត្យ">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <small>គ្មានអ្នកជំងឺក្នុងបញ្ជីរង់ចាំទេ។</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@stop
