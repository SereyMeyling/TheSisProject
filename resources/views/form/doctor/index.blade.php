@extends('adminlte::page')

@section('title', 'បញ្ជីអ្នកជំងឺរង់ចាំជួបគ្រូពេទ្យ')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="font-weight-bold text-dark"><i class="fas fa-user-md mr-2 text-success"></i> បន្ទប់ពិនិត្យ និងព្យាបាលគ្រូពេទ្យ</h4>
        <span class="badge badge-primary p-2 font-weight-bold" style="font-size: 14px;">
            <i class="far fa-clock mr-1"></i> ថ្ងៃនេះ: {{ date('d-m-Y') }}
        </span>
    </div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-lg">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-procedures mr-1"></i> បញ្ជីអ្នកជំងឺរង់ចាំជួបគ្រូពេទ្យទាំងអស់</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small">
                            <tr>
                                <th>កូដអ្នកជំងឺ</th>
                                <th>ឈ្មោះពេញ</th>
                                <th>ភេទ</th>
                                <th>ម៉ោងមកដល់</th>
                                <th>រោគវិនិច្ឆ័យបឋម</th>
                                <th class="text-center">សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($waitingPatients as $record)
                                <tr>
                                    <td class="font-weight-bold text-dark align-middle">{{ $record->patient->patient_code ?? 'N/A' }}</td>
                                    <td class="font-weight-bold text-success align-middle">{{ $record->patient->full_name ?? 'N/A' }}</td>
                                    <td class="align-middle">{{ $record->patient->sex == 'Male' ? 'ប្រុស' : 'ស្រី' }}</td>
                                    <td class="align-middle"><small class="text-muted">{{ $record->visit_date }}</small></td>
                                    <td class="align-middle">{{ Str::limit($record->diagnosis ?? 'មិនទាន់មាន', 20) }}</td>
                                    <td class="text-center align-middle">
                                        <a href="{{ route('doctor.consultation', $record->record_id) }}" class="btn btn-sm btn-info font-weight-bold shadow-sm">
                                            <i class="fas fa-stethoscope mr-1"></i> ពិនិត្យជំងឺ
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">គ្មានអ្នកជំងឺរង់ចាំពិនិត្យទេ។</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end py-2">
                {{ $waitingPatients->links() }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-lg border-left border-warning">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-warning"><i class="fas fa-list-ol mr-1"></i> អ្នកជំងឺរង់ចាំបន្ទាប់</h6>
                <span class="badge badge-warning text-white">{{ $waitingPatients->count() }} នាក់</span>
            </div>
            <div class="card-body p-2" style="max-height: 420px; overflow-y: auto;">
                @forelse($waitingPatients->skip(0) as $index => $nextPatient)
                    <div class="d-flex align-items-center justify-content-between border-bottom p-2 mb-2 bg-light rounded shadow-sm">
                        <div>
                            <span class="badge badge-pill badge-success mr-1">#{{ $index + 1 }}</span>
                            <span class="font-weight-bold text-dark text-sm">{{ $nextPatient->patient->full_name ?? 'N/A' }}</span>
                            <br>
                            <small class="text-muted ml-4"><i class="far fa-id-card"></i> {{ $nextPatient->patient->patient_code ?? '' }}</small>
                        </div>
                        <div>
                            <a href="{{ route('doctor.consultation', $nextPatient->record_id) }}" class="btn btn-xs btn-outline-success font-weight-bold" title="ចូលពិនិត្យ">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3">
                        <small>គ្មានអ្នកជំងឺក្នុងបញ្ជីរង់ចាំទេ។</small>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-lg bg-gradient-success text-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="font-weight-bold mb-1"><i class="fas fa-shield-alt mr-1"></i> ប្រព័ន្ធគ្រប់គ្រងវេជ្ជសាស្ត្រ</h6>
                        <p class="mb-0 text-white-50 small">ត្រួតពិនិត្យរោគវិនិច្ឆ័យ និងបញ្ជូនគោលដៅអ្នកជំងឺដោយសុវត្ថិភាព។</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
