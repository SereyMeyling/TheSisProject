@extends('adminlte::page')

@section('title', 'Pharmacy Dashboard')

@section('content')
<div class="container-fluid pt-3">
    {{-- Header Banner --}}
    <div class="card bg-gradient-success text-white mb-4 shadow-sm" style="border-radius: 16px; background: linear-gradient(135deg, #11998e, #38ef7d);">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h2 class="font-weight-bold mb-1"><i class="fas fa-pills mr-2"></i>ផ្ទាំងគ្រប់គ្រងឱសថស្ថាន (Pharmacy Dashboard)</h2>
                <p class="mb-0 text-white-50">ការគ្រប់គ្រងស្តុកថ្នាំ ថ្នាំជិតផុតកំណត់ ការលក់ និងវេជ្ជបញ្ជា។</p>
            </div>
            <div>
                <a href="{{ route('pharmacy.sell.index') }}" class="btn btn-light font-weight-bold px-4 py-2 text-success mr-2" style="border-radius: 20px;">
                    <i class="fas fa-cash-register mr-1"></i> លក់ថ្នាំ (POS)
                </a>
                <a href="{{ route('pharmacy.index') }}" class="btn btn-outline-light font-weight-bold px-3 py-2" style="border-radius: 20px;">
                    <i class="fas fa-boxes mr-1"></i> បញ្ជីស្តុក
                </a>
            </div>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-danger" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #ffebee;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="text-muted small">ថ្នាំជិតអស់ស្តុក (Low Stock)</div>
                        <div class="h3 font-weight-bold mb-0 text-danger">{{ $lowStockBatches->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-warning" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #fff8e1;">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div>
                        <div class="text-muted small">ថ្នាំជិតផុតកំណត់ (Expiring)</div>
                        <div class="h3 font-weight-bold mb-0 text-warning">{{ $expiringBatches->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-info" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #e0f7fa;">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <div>
                        <div class="text-muted small">វេជ្ជបញ្ជារង់ចាំ (Prescriptions)</div>
                        <div class="h3 font-weight-bold mb-0 text-dark">{{ $pendingPrescriptions->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-circle mr-3 text-success" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: #e8f5e9;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="text-muted small">ការលក់ថ្ងៃនេះ (Today Sales)</div>
                        <div class="h3 font-weight-bold mb-0 text-success">${{ number_format($todaySalesTotal, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN TABLES --}}
    <div class="row">
        {{-- Expiring Medicine & Low Stock Alerts --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0 text-danger"><i class="fas fa-exclamation-circle mr-2"></i>ថ្នាំជិតផុតកំណត់ (Expiring Soon - 30 Days)</h5>
                    <a href="{{ route('pharmacy.expiring.detail') }}" class="small font-weight-bold">មើលទាំងអស់</a>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="pl-4">ឈ្មោះថ្នាំ (Medicine)</th>
                                <th>លេខបាច់ (Batch)</th>
                                <th>ថ្ងៃផុតកំណត់ (Expiry)</th>
                                <th class="text-right pr-4">ចំនួនសល់</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiringBatches as $batch)
                            <tr>
                                <td class="pl-4 font-weight-bold">{{ $batch->medicine->medicine_name ?? 'N/A' }}</td>
                                <td><span class="badge badge-light border">{{ $batch->batch_number }}</span></td>
                                <td class="text-danger font-weight-bold small">{{ optional($batch->expiry_date)->format('Y-m-d') }}</td>
                                <td class="text-right pr-4 font-weight-bold">{{ $batch->remaining_quantity }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">គ្មានថ្នាំជិតផុតកំណត់ទេ</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pending Prescriptions --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-prescription-bottle-alt text-info mr-2"></i>បញ្ជីវេជ្ជបញ្ជាចុងក្រោយ (Prescriptions)</h5>
                    <a href="{{ route('pharmacy.prescriptions.index') }}" class="small font-weight-bold">មើលទាំងអស់</a>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="pl-4">កូដ (ID)</th>
                                <th>អ្នកជំងឺ (Patient)</th>
                                <th>កាលបរិច្ឆេទ</th>
                                <th class="text-right pr-4">សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingPrescriptions as $pres)
                            <tr>
                                <td class="pl-4 font-weight-bold">#RX-{{ $pres->prescription_id }}</td>
                                <td>{{ $pres->medicalRecord->patient->name ?? 'N/A' }}</td>
                                <td class="small text-muted">{{ optional($pres->prescribed_date)->format('Y-m-d') }}</td>
                                <td class="text-right pr-4">
                                    <a href="{{ route('pharmacy.prescriptions.index') }}" class="btn btn-sm btn-outline-success" style="border-radius: 12px;">
                                        <i class="fas fa-check-circle"></i> ចែកថ្នាំ
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">គ្មានវេជ្ជបញ្ជារង់ចាំទេ</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
