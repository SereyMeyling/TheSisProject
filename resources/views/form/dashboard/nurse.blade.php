@extends('adminlte::page')

@section('title', 'Nurse Dashboard')

@section('content')
<div class="container-fluid pt-3">
    {{-- Header Banner --}}
    <div class="card text-white mb-4 shadow-sm" style="background-color: #006D36; border-radius: 16px;">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h2 class="font-weight-bold mb-1">
                    <i class="fas fa-user-nurse mr-2"></i>
                    ផ្ទាំងគ្រប់គ្រងគិលានុបដ្ឋាយិកា (Nurse Dashboard)
                </h2>

                <p class="mb-0 text-white-50">
                    ការគ្រប់គ្រងបន្ទប់សម្រាកព្យាបាល ការចូលសម្រាកព្យាបាល (Admissions) និងការថែទាំអ្នកជំងឺ។
                </p>
            </div>

            <a href="{{ route('patients.create') }}" class="btn btn-light font-weight-bold px-4 py-2"
                style="border-radius: 20px; color: var(--primary-color);">
                <i class="fas fa-user-plus mr-1"></i>
                ចុះឈ្មោះអ្នកជំងឺ
            </a>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-danger"
                        style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #ffebee;">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div>
                        <div class="text-muted small">អត្រាប្រើប្រាស់គ្រែ (Occupancy)</div>
                        <div class="h3 font-weight-bold mb-0 text-danger">{{ $occupancyRate }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-warning"
                        style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #fff8e1;">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div>
                        <div class="text-muted small">បន្ទប់កំពុងប្រើ (Occupied)</div>
                        <div class="h3 font-weight-bold mb-0 text-dark">{{ $occupiedRooms }} / {{ $totalRooms }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-success"
                        style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #e8f5e9;">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div>
                        <div class="text-muted small">បន្ទប់ទំនេរ (Available)</div>
                        <div class="h3 font-weight-bold mb-0 text-success">{{ $availableRooms }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-info"
                        style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #e0f7fa;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <div class="text-muted small">អ្នកជំងឺកំពុងសម្រាក (Inpatients)</div>
                        <div class="h3 font-weight-bold mb-0 text-info">{{ $activeAdmissions->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN TABLES --}}
    <div class="row">
        {{-- Active Inpatient Admissions --}}
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0 text-dark"><i
                            class="fas fa-procedures text-danger mr-2"></i>បញ្ជីអ្នកជំងឺកំពុងសម្រាកព្យាបាល (Active
                        Inpatients)</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="pl-4">អ្នកជំងឺ (Patient)</th>
                                <th>បន្ទប់ (Room)</th>
                                <th>កាលបរិច្ឆេទចូល</th>
                                <th>ស្ថានភាព (Status)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeAdmissions as $adm)
                                <tr>
                                    <td class="pl-4 font-weight-bold text-dark">{{ $adm->patient->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-door-closed"></i>
                                            {{ $adm->room->room_number ?? 'Room' }}</span>
                                    </td>
                                    <td class="small text-muted">{{ optional($adm->admission_date)->format('Y-m-d H:i') }}
                                    </td>
                                    <td>
                                        <span class="badge badge-pill badge-warning px-3 py-1">Admitted</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">គ្មានអ្នកជំងឺកំពុងសម្រាកព្យាបាលទេ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Patient Registrations --}}
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0 text-dark"><i
                            class="fas fa-user-plus text-primary mr-2"></i>អ្នកជំងឺចុះឈ្មោះថ្មីៗ (Recent Patients)</h5>
                    <a href="{{ route('patients.index') }}" class="small font-weight-bold">មើលទាំងអស់</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($recentPatients as $pat)
                            <li class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $pat->name }}</div>
                                    <div class="small text-muted">{{ $pat->phone ?? 'No phone' }} | Gender:
                                        {{ ucfirst($pat->gender ?? 'N/A') }}
                                    </div>
                                </div>
                                <a href="{{ route('patients.show', $pat->patient_id) }}"
                                    class="btn btn-sm btn-light text-primary"><i class="fas fa-eye"></i></a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">គ្មានទិន្នន័យអ្នកជំងឺថ្មីទេ</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@stop