@extends('adminlte::page')

@section('title', 'បញ្ជី Medical Records')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.4rem;">
            <i class="fas fa-file-medical text-success mr-2"></i> បញ្ជីកំណត់ត្រាវេជ្ជសាស្ត្រ (Medical Records)
        </h1>
        <a href="{{ route('medical-records.create') }}" class="btn btn-success px-3 rounded-pill shadow-sm" style="background-color: #00695c;">
            <i class="fas fa-plus mr-1"></i> បង្កើត Record ថ្មី
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-lg">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small">
                            <th>ID</th>
                            <th>អ្នកជំងឺ</th>
                            <th>កាលបរិច្ឆេទពិនិត្យ</th>
                            <th>សញ្ញាជីវិត (Vitals)</th>
                            <th>ការវិនិច្ឆ័យ (Diagnosis)</th>
                            <th>គ្រូពេទ្យ</th>
                            <th>សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td class="font-weight-bold text-dark">#MR-{{ $record->record_id }}</td>
                                <td>
                                    <span class="font-weight-bold text-success d-block">{{ $record->patient->full_name ?? 'N/A' }}</span>
                                    <small class="text-muted">{{ $record->patient->patient_code ?? '' }}</small>
                                </td>
                                <td>{{ $record->visit_date ? $record->visit_date->format('d/m/Y H:i') : '-' }}</td>
                                <td>
                                    <small class="d-block"><strong>BP:</strong> {{ $record->bp_systolic ?? '-' }}/{{ $record->bp_diastolic ?? '-' }} mmHg</small>
                                    <small class="d-block"><strong>HR:</strong> {{ $record->heart_rate ?? '-' }} bpm | <strong>Temp:</strong> {{ $record->temperature ?? '-' }}°C</small>
                                </td>
                                <td>{{ Str::limit($record->diagnosis ?? 'មិនទាន់មាន', 30) }}</td>
                                <td>{{ $record->doctor ? ($record->doctor->first_name . ' ' . $record->doctor->last_name) : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('medical-records.show', $record->record_id) }}" class="btn btn-sm btn-light text-info rounded-circle"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('medical-records.edit', $record->record_id) }}" class="btn btn-sm btn-light text-primary rounded-circle"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">មិនទាន់មានទិន្នន័យ Medical Record ឡើយ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-2">
                {{ $records->links() }}
            </div>
        </div>
    </div>
</div>
@stop
