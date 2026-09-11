@extends('adminlte::page')

@section('title', 'Appointment Details')

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-calendar-check"></i>
                ព័ត៌មានការណាត់ជួប
            </h2>

            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                ត្រឡប់
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    ព័ត៌មាន Appointment #{{ $appointment->appointment_id }}
                </h3>
            </div>

            <div class="card-body">

                <div class="row">

                    {{-- Patient --}}
                    <div class="col-md-6 mb-3">
                        <strong>អ្នកជំងឺ:</strong>
                        <p class="mb-0">
                            {{ optional($appointment->patient)->full_name ?? 'N/A' }}
                        </p>
                    </div>

                    {{-- Patient Code --}}
                    <div class="col-md-6 mb-3">
                        <strong>Patient Code:</strong>
                        <p class="mb-0">
                            {{ optional($appointment->patient)->patient_code ?? 'N/A' }}
                        </p>
                    </div>

                    {{-- Doctor --}}
                    <div class="col-md-6 mb-3">
                        <strong>វេជ្ជបណ្ឌិត:</strong>
                        <p class="mb-0">
                            {{ optional($appointment->doctor)->first_name ?? '' }}
                            {{ optional($appointment->doctor)->last_name ?? '' }}
                        </p>
                    </div>

                    {{-- Date --}}
                    <div class="col-md-6 mb-3">
                        <strong>កាលបរិច្ឆេទ:</strong>
                        <p class="mb-0">
                            {{ $appointment->appointment_date
        ? $appointment->appointment_date->format('d/m/Y H:i')
        : 'N/A' }}
                        </p>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">
                        <strong>ស្ថានភាព:</strong>
                        <p class="mb-0">

                            @if($appointment->status === 'scheduled')
                                <span class="badge badge-primary">
                                    Scheduled
                                </span>

                            @elseif($appointment->status === 'completed')
                                <span class="badge badge-success">
                                    Completed
                                </span>

                            @elseif($appointment->status === 'cancelled')
                                <span class="badge badge-danger">
                                    Cancelled
                                </span>

                            @else
                                <span class="badge badge-secondary">
                                    {{ ucfirst($appointment->status) }}
                                </span>
                            @endif

                        </p>
                    </div>

                    {{-- Reason --}}
                    <div class="col-md-6 mb-3">
                        <strong>មូលហេតុ:</strong>
                        <p class="mb-0">
                            {{ $appointment->reason ?? 'N/A' }}
                        </p>
                    </div>

                </div>

            </div>

            <div class="card-footer">

                <a href="{{ route('appointment.edit', $appointment->appointment_id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    កែប្រែ
                </a>

                <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                    <i class="fas fa-list"></i>
                    បញ្ជី Appointment
                </a>

            </div>
        </div>

    </div>
@endsection