@extends('adminlte::page')

@section('title', 'Doctor Dashboard')

@section('content')
<div class="container-fluid pt-3">
    {{-- Header banner --}}
    <div class="card bg-gradient-primary text-white mb-4 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h2 class="font-weight-bold mb-1"><i class="fas fa-user-md mr-2"></i>ផ្ទាំងគ្រប់គ្រងវេជ្ជបណ្ឌិត (Doctor Dashboard)</h2>
                <p class="mb-0 text-white-50">សូមស្វាគមន៍! ខាងក្រោមនេះជាបញ្ជីការណាត់ជួប និងពិនិត្យជំងឺប្រចាំថ្ងៃរបស់អ្នក។</p>
            </div>
            <a href="{{ route('patients.index') }}" class="btn btn-light text-primary font-weight-bold px-4 py-2" style="border-radius: 20px;">
                <i class="fas fa-plus-circle mr-1"></i> អ្នកជំងឺថ្មី
            </a>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-light-primary text-primary mr-3" style="width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: #e8f0fe;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small">ការណាត់ជួបថ្ងៃនេះ (Today)</div>
                        <div class="h3 font-weight-bold mb-0 text-dark">{{ $todayAppointmentsCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-light-warning text-warning mr-3" style="width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: #fff8e1;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-muted small">រង់ចាំការពិនិត្យ (Pending)</div>
                        <div class="h3 font-weight-bold mb-0 text-dark">{{ $pendingAppointments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle bg-light-info text-info mr-3" style="width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: #e0f7fa;">
                        <i class="fas fa-vials"></i>
                    </div>
                    <div>
                        <div class="text-muted small">សំណើពិសោធន៍រង់ចាំ (Lab Orders)</div>
                        <div class="h3 font-weight-bold mb-0 text-dark">{{ $pendingLabOrders->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN TABLES --}}
    <div class="row">
        {{-- Appointments Table --}}
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-calendar-alt text-primary mr-2"></i>ការណាត់ជួបថ្ងៃនេះ (Today's Queue)</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="pl-4">អ្នកជំងឺ (Patient)</th>
                                <th>កាលបរិច្ឆេទ (Date)</th>
                                <th>មូលហេតុ (Reason)</th>
                                <th>ស្ថានភាព (Status)</th>
                                <th class="text-right pr-4">សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $app)
                            <tr>
                                <td class="pl-4 font-weight-bold text-dark">
                                    {{ $app->patient->name ?? 'N/A' }}
                                </td>
                                <td class="small text-muted">{{ optional($app->appointment_date)->format('H:i A') ?? '-' }}</td>
                                <td class="small">{{ Str::limit($app->reason ?? '-', 25) }}</td>
                                <td>
                                    <span class="badge badge-pill badge-warning px-3 py-1">រង់ចាំពិនិត្យ</span>
                                </td>
                                <td class="text-right pr-4">
                                    @if(isset($app->patient_id))
                                    <a href="{{ route('patients.show', $app->patient_id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 12px;">
                                        <i class="fas fa-stethoscope"></i> ពិនិត្យ
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">គ្មានការណាត់ជួបសម្រាប់ថ្ងៃនេះទេ</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pending Lab Orders & Recent Consultations --}}
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-flask text-info mr-2"></i>សំណើពិសោធន៍រង់ចាំ (Pending Lab Orders)</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="pl-4">កូដសំណើ (Order ID)</th>
                                <th>អ្នកជំងឺ (Patient)</th>
                                <th>កាលបរិច្ឆេទ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLabOrders as $lab)
                            <tr>
                                <td class="pl-4 font-weight-bold">#LAB-{{ $lab->lab_order_id }}</td>
                                <td>{{ $lab->medicalRecord->patient->name ?? 'N/A' }}</td>
                                <td class="small text-muted">{{ optional($lab->order_date)->format('Y-m-d') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">គ្មានសំណើពិសោធន៍រង់ចាំទេ</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-file-medical text-success mr-2"></i>កំណត់ត្រាវេជ្ជសាស្ត្រចុងក្រោយ</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush mb-0">
                        @forelse($recentMedicalRecords as $rec)
                        <li class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="font-weight-bold text-dark">{{ $rec->patient->name ?? 'Patient' }}</div>
                                <div class="small text-muted">{{ Str::limit($rec->diagnosis ?? 'No diagnosis recorded', 35) }}</div>
                            </div>
                            <a href="{{ route('medical-records.show', $rec->record_id) }}" class="btn btn-sm btn-light text-primary"><i class="fas fa-eye"></i></a>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted py-3">គ្មានកំណត់ត្រាថ្មីៗទេ</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
