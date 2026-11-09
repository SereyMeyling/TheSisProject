@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid dash-wrap">
    {{-- STAT CARDS --}}
    <div class="row stat-row">

        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-violet"><i class="fas fa-user-injured"></i></span>
                    <span class="stat-trend up"><i class="fas fa-check"></i> សកម្ម</span>
                </div>
                <div class="stat-label">អ្នកជំងឺសរុប</div>
                <div class="stat-value">{{ number_format($totalPatients ?? 0) }}</div>
                <a href="{{ route('patients.index') }}" class="btn btn-export text-center">គ្រប់គ្រង</a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-blue"><i class="fas fa-users-cog"></i></span>
                    <span class="stat-trend up"><i class="fas fa-user-shield"></i> សរុប</span>
                </div>
                <div class="stat-label">អ្នកប្រើប្រាស់</div>
                <div class="stat-value">{{ number_format($totalUsers ?? 0) }}</div>
                <a href="{{ route('user.index') }}" class="btn btn-export text-center">គ្រប់គ្រង</a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-pink"><i class="fas fa-pills"></i></span>
                    <span class="stat-trend down"><i class="fas fa-boxes"></i> ស្តុក</span>
                </div>
                <div class="stat-label">ថ្នាំសរុប</div>
                <div class="stat-value">{{ number_format($totalMedicines ?? 0) }}</div>
                <a href="{{ route('pharmacy.index') }}" class="btn btn-export text-center">ឱសថស្ថាន</a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-teal"><i class="fas fa-wallet"></i></span>
                    <span class="stat-trend up"><i class="fas fa-calendar-day"></i> ថ្ងៃនេះ</span>
                </div>
                <div class="stat-label">ចំណូលប្រចាំថ្ងៃ</div>
                <div class="stat-value">${{ number_format($todayRevenue ?? 0, 2) }}</div>
                <a href="{{ route('billing.index') }}" class="btn btn-export text-center">ការទូទាត់</a>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-green"><i class="fas fa-hand-holding-usd"></i></span>
                    <span class="stat-trend up"><i class="fas fa-chart-line"></i> សរុប</span>
                </div>
                <div class="stat-label">ចំណូលសរុប</div>
                <div class="stat-value">${{ number_format($totalRevenue ?? 0, 2) }}</div>
                <a href="{{ route('billing.index') }}" class="btn btn-export text-center">ការទូទាត់</a>
            </div>
        </div>

    </div>

    {{-- ALERT CARDS --}}
    <div class="row stat-row">

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-rose"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="stat-trend down"><i class="fas fa-clock"></i> Pending</span>
                </div>
                <div class="stat-label">វិក្កយបត្រមិនទាន់បង់</div>
                <div class="stat-value">{{ number_format($unpaidCount) }}</div>
                <div class="stat-sub">នៅជំពាក់ ${{ number_format($unpaidBalance, 2) }}</div>
                <a href="{{ route('billing.index') }}" class="btn btn-export text-center">ការទូទាត់</a>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-pink"><i class="fas fa-box-open"></i></span>
                    <span class="stat-trend down"><i class="fas fa-exclamation"></i> ស្តុក</span>
                </div>
                <div class="stat-label">ថ្នាំជិតអស់ស្តុក</div>
                <div class="stat-value">{{ number_format($lowStockCount) }}</div>
                <div class="stat-sub">ត្រូវបញ្ជាទិញបន្ថែម</div>
                <a href="{{ route('pharmacy.index') }}" class="btn btn-export text-center">ឱសថស្ថាន</a>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-blue"><i class="fas fa-hourglass-end"></i></span>
                    <span class="stat-trend down"><i class="fas fa-calendar-times"></i> 30 ថ្ងៃ</span>
                </div>
                <div class="stat-label">ថ្នាំជិតផុតកំណត់</div>
                <div class="stat-value">{{ number_format($expiringCount) }}</div>
                <div class="stat-sub">ក្នុងរយៈពេល 30 ថ្ងៃ</div>
                <a href="{{ route('pharmacy.index') }}" class="btn btn-export text-center">ឱសថស្ថាន</a>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-teal"><i class="fas fa-balance-scale"></i></span>
                    <span class="stat-trend up"><i class="fas fa-calendar-alt"></i> ខែនេះ</span>
                </div>
                <div class="stat-label">ចំណូលសុទ្ធ (ចំណូល − ចំណាយ)</div>
                <div class="stat-value {{ ($monthIncome - $monthExpense) < 0 ? 'text-danger' : '' }}">
                    ${{ number_format($monthIncome - $monthExpense, 2) }}
                </div>
                <div class="stat-sub">ចំណូល ${{ number_format($monthIncome, 2) }}</div>
                <a href="{{ route('billing.index') }}" class="btn btn-export text-center">ការទូទាត់</a>
            </div>
        </div>

    </div>

    {{-- MIDDLE ROW: income/expense chart, today's summary, occupancy --}}
    <div class="row mb-3">

        <div class="col-lg-5 mb-3 mb-lg-0">
            <div class="panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-title">ចំណូល និង ចំណាយ</h3>
                    <div class="range-toggle" role="group">
                        <button type="button" class="range-btn income-range-btn active"
                            data-range="yearly">ប្រចាំឆ្នាំ</button>
                        <button type="button" class="range-btn income-range-btn" data-range="monthly">ប្រចាំខែ</button>
                    </div>
                </div>
                <div class="chart-holder">
                    <canvas id="incomeExpenseChart" height="180"></canvas>
                </div>
                <div class="chart-legend">
                    <span><i class="legend-dot dot-green"></i> ចំណូល</span>
                    <span><i class="legend-dot dot-pink"></i> ចំណាយ</span>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">សកម្មភាពថ្ងៃនេះ</h3>

                <div class="today-row">
                    <span class="today-icon bg-soft-green"><i class="fas fa-calendar-check"></i></span>
                    <div class="today-text">
                        <div class="today-value">{{ $todayAppointments }}</div>
                        <div class="today-label">ការណាត់ជួប</div>
                    </div>
                </div>
                <!-- <div class="today-row">
                    <span class="today-icon bg-soft-red"><i class="fas fa-notes-medical"></i></span>
                    <div class="today-text">
                        <div class="today-value">{{ $emergencyCases }}</div>
                        <div class="today-label">ករណីបន្ទាន់</div>
                    </div>
                </div> -->
                <div class="today-row">
                    <span class="today-icon bg-soft-blue"><i class="fas fa-bed"></i></span>
                    <div class="today-text">
                        <div class="today-value">{{ $availableRooms }}</div>
                        <div class="today-label">បន្ទប់ទំនេរ</div>
                    </div>
                </div>

                <a href="{{ route('appointment.index') }}"
                    class="btn-view-more text-center text-decoration-none d-block">មើលបន្ថែម</a>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="panel h-100 text-center d-flex flex-column">
                <h3 class="panel-title text-left mb-2">ចំនួនគ្រែ</h3>
                <div class="donut-holder">
                    <canvas id="occupancyChart" width="150" height="150"></canvas>
                    <div class="donut-center">{{ $occupancyPercent }}%<span>កំពុងប្រើ</span></div>
                </div>
                <div class="donut-stats mt-auto">
                    <div class="donut-stat">
                        <div class="donut-stat-label">សរុប</div>
                        <div class="donut-stat-value">{{ number_format($totalRooms) }}</div>

                    </div>
                    <div class="donut-stat">
                        <div class="donut-stat-label">នៅសល់</div>
                        <div class="donut-stat-value text-danger">{{ $availableRooms }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- PAYMENT METHODS / VISIT TYPES / MONTH SUMMARY --}}
    <div class="row mb-3">

        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">វិធីទូទាត់ (ខែនេះ)</h3>
                @if(count($paymentMix))
                    <div class="chart-holder h-220"><canvas id="paymentChart"></canvas></div>
                @else
                    <p class="text-muted small mb-0">មិនទាន់មានការទូទាត់ក្នុងខែនេះ</p>
                @endif
            </div>
        </div>

        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">ប្រភេទមកពិនិត្យ</h3>
                @if(array_sum($visitTypeStats['data']) > 0)
                    <div class="chart-holder h-220"><canvas id="visitTypeChart"></canvas></div>
                    <p class="text-muted small mt-2 mb-0">MF = Meet follow-up · BF = Before follow-up · FU = Follow-up</p>
                @else
                    <p class="text-muted small mb-0">មិនទាន់មានទិន្នន័យ (ត្រូវជ្រើសប្រភេទមកពិនិត្យក្នុងកំណត់ត្រាពេទ្យ)</p>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">សង្ខេបហិរញ្ញវត្ថុ (ខែនេះ)</h3>
                <div class="money-row">
                    <span class="money-label">ចំណូល</span>
                    <span class="money-value in">${{ number_format($monthIncome, 2) }}</span>
                </div>
                <div class="money-row">
                    <span class="money-label">ចំណាយ (ថ្នាំ + ផ្សេងៗ)</span>
                    <span class="money-value out">${{ number_format($monthExpense, 2) }}</span>
                </div>
                <div class="money-row">
                    <span class="money-label">សុទ្ធ</span>
                    <span class="money-value {{ ($monthIncome - $monthExpense) < 0 ? 'out' : 'in' }}">
                        ${{ number_format($monthIncome - $monthExpense, 2) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- DISEASE STATISTICS + ADMITTED BY DEPARTMENT --}}
    <div class="row mb-3">

        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-title">ស្ថិតិជំងឺតាមភេទ</h3>
                    <input type="month" id="diseaseMonth" class="form-control form-control-sm month-input"
                        value="{{ $selectedMonth }}" max="{{ now()->format('Y-m') }}">
                </div>
                <div class="chart-holder h-260"><canvas id="diseaseChart"></canvas></div>
                <div class="chart-legend">
                    <span><i class="legend-dot dot-blue"></i> ប្រុស {{ $diseaseStats['maleTotal'] }}</span>
                    <span><i class="legend-dot dot-pink"></i> ស្រី {{ $diseaseStats['femaleTotal'] }}</span>
                    <span>សរុប {{ $diseaseStats['maleTotal'] + $diseaseStats['femaleTotal'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">អ្នកជំងឺនៅរដ្ឋបាលតាមផ្នែក</h3>

                @forelse($departmentBreakdown as $dept)
                    <div class="dept-row">
                        <span class="dept-icon bg-soft-rose"><i class="fas fa-heartbeat"></i></span>
                        <div class="dept-text">
                            <div class="dept-name">{{ $dept['name'] }}</div>
                            <div class="dept-sub">{{ $dept['total'] }} នាក់កំពុងសម្រាក</div>
                        </div>
                        <div class="dept-percent">{{ $dept['percent'] }}%</div>
                    </div>
                @empty
                    <p class="text-muted small">មិនទាន់មានទិន្នន័យ (ត្រូវកំណត់ department ទៅបន្ទប់សិន)</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- TODAY'S PATIENTS + PHARMACY ALERTS --}}
    <div class="row mb-3">

        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-title">អ្នកជំងឺថ្ងៃនេះ</h3>
                    <span class="text-muted small">សរុប {{ $todayVisits['total'] }} នាក់</span>
                </div>
                <div class="panel-scroll">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>PT Code</th>
                                <th>ឈ្មោះ</th>
                                <th>ភេទ</th>
                                <th>រោគ</th>
                                <th>មកពិនិត្យ</th>
                                <th class="text-right">បានបង់</th>
                                <th>ស្ថានភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayVisits['visits'] as $i => $v)
                                @php
                                    $pid = $v->patient_id;
                                    $pay = $todayVisits['paid'][$pid] ?? null;
                                    $owe = (float) ($todayVisits['owing'][$pid] ?? 0);
                                    $sex = $v->patient->sex ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="font-weight-bold">{{ $v->patient->patient_code ?? '-' }}</td>
                                    <td>{{ $v->patient->full_name ?? '-' }}</td>
                                    <td>{{ $sex === 'male' ? 'ប្រុស' : ($sex === 'female' ? 'ស្រី' : '-') }}</td>
                                    <td>{{ \App\Models\MedicalRecord::DIAGNOSIS_GROUPS[$v->diagnosis_group] ?? ($v->diagnosis ?: '-') }}</td>
                                    <td>
                                        @if($v->visit_type)
                                            <span class="visit-badge vt-{{ $v->visit_type }}">{{ \App\Models\MedicalRecord::VISIT_TYPES[$v->visit_type] ?? strtoupper($v->visit_type) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($pay)
                                            ${{ number_format($pay->total, 2) }}
                                            <small class="text-muted">{{ strtoupper(str_replace(',', '/', $pay->methods)) }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($owe > 0)
                                            <span class="status-pill pending">មិនទាន់បង់</span>
                                        @elseif($pay)
                                            <span class="status-pill paid">បានបង់</span>
                                        @else
                                            <span class="status-pill none">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">មិនទាន់មានអ្នកជំងឺមកពិនិត្យថ្ងៃនេះ</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($todayVisits['total'] > $todayVisits['visits']->count())
                    <p class="text-muted small mt-2 mb-0">បង្ហាញ {{ $todayVisits['visits']->count() }} ចុងក្រោយ ក្នុងចំណោម {{ $todayVisits['total'] }}</p>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">ការជូនដំណឹងឱសថស្ថាន</h3>

                <div class="alert-title">ថ្នាំជិតអស់ស្តុក</div>
                @forelse($lowStockList as $m)
                    <div class="alert-item">
                        <span>{{ $m->medicine_name }}</span>
                        <span class="text-danger font-weight-bold">{{ (int) $m->stock_total }}</span>
                    </div>
                @empty
                    <p class="text-muted small">គ្មានថ្នាំជិតអស់ស្តុក</p>
                @endforelse

                <div class="alert-title mt-3">ថ្នាំជិតផុតកំណត់</div>
                @forelse($expiringList as $b)
                    <div class="alert-item">
                        <span>{{ $b->medicine->medicine_name ?? '-' }}</span>
                        <span class="text-muted">នៅសល់ {{ (int) $b->remaining_quantity }}</span>
                    </div>
                @empty
                    <p class="text-muted small">គ្មានថ្នាំជិតផុតកំណត់</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- NEW PATIENTS PROGRESS + SYSTEM --}}
    <div class="row">

        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-title">ការវិវត្តអ្នកជំងឺ</h3>
                    <div class="range-toggle" role="group">
                        <button type="button" class="range-btn patient-range-btn active"
                            data-range="weekly">ប្រចាំសប្តាហ៍</button>
                        <button type="button" class="range-btn patient-range-btn" data-range="monthly">ប្រចាំខែ</button>
                    </div>
                </div>
                <div class="chart-holder">
                    <canvas id="weeklyChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">ប្រព័ន្ធ</h3>

                @php $lastBackup = $backupLogs->first(); @endphp
                <div class="today-row">
                    <span class="today-icon bg-soft-blue"><i class="fas fa-database"></i></span>
                    <div class="today-text">
                        <div class="today-value">{{ $lastBackup && $lastBackup->created_at ? $lastBackup->created_at->format('d/m/Y H:i') : 'មិនទាន់មាន' }}</div>
                        <div class="today-label">Backup ចុងក្រោយ</div>
                    </div>
                </div>

                <div class="alert-title mt-3">អ្នកប្រើប្រាស់ថ្មីៗ</div>
                @foreach($recentUsers as $u)
                    <div class="alert-item">
                        <span>{{ $u->name }}</span>
                        <span class="text-muted small">{{ optional($u->created_at)->format('d/m/Y') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@stop

@section('css')
@parent
<style>
    :root {
        --primary-color: #006D36;
        --primary-soft: #E7F4EC;
        --ink: #1F2A24;
        --muted: #7C8A82;
        --border: #EDF1EE;
        --danger: #E8506B;
    }

    body,
    .dash-wrap {
        color: var(--ink);
    }

    .dash-wrap {
        padding-top: .5rem;
    }

    /* ---- stat cards ---- */
    .stat-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        height: 100%;
        box-shadow: 0 2px 10px rgba(31, 42, 36, .04);
    }

    .stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .icon-violet {
        background: #EEE9FE;
        color: #7C4DFF;
    }

    .icon-blue {
        background: #E4F0FF;
        color: #2F80ED;
    }

    .icon-pink {
        background: #FDE9F3;
        color: #E0559C;
    }

    .icon-teal {
        background: #E1F6EF;
        color: #14A97F;
    }

    .icon-rose {
        background: #FCE9EC;
        color: #E0556B;
    }

    .icon-green {
        background: var(--primary-soft);
        color: var(--primary-color);
    }

    .stat-trend {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
    }

    .stat-trend.up {
        background: #E4F7EC;
        color: #1BA860;
    }

    .stat-trend.down {
        background: #FDE9EA;
        color: #E0455A;
    }

    .stat-label {
        font-size: 12px;
        color: var(--muted);
        margin-top: 10px;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 10px;
    }

    .btn-export {
        width: 100%;
        border: none;
        background: var(--primary-color);
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.3;
        border-radius: 20px;
        padding: 6px 4px;
    }

    /* ---- panels ---- */
    .panel {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(31, 42, 36, .04);
    }

    .panel-title {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }

    .range-toggle {
        background: #F5F7F6;
        border-radius: 20px;
        padding: 3px;
        display: inline-flex;
    }

    .range-btn {
        border: none;
        background: transparent;
        font-size: 12px;
        font-weight: 600;
        color: var(--muted);
        padding: 5px 14px;
        border-radius: 16px;
    }

    .range-btn.active {
        background: #fff;
        color: var(--ink);
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
    }

    .chart-holder {
        position: relative;
        width: 100%;
    }

    .chart-legend {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 8px;
        font-size: 12px;
        color: var(--muted);
    }

    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .dot-green {
        background: var(--primary-color);
    }

    .dot-pink {
        background: #E0559C;
    }

    /* ---- today's summary ---- */
    .today-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }

    .today-row:last-of-type {
        border-bottom: none;
    }

    .today-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .bg-soft-green {
        background: var(--primary-soft);
        color: var(--primary-color);
    }

    .bg-soft-red {
        background: #FDE9EA;
        color: #E0455A;
    }

    .bg-soft-blue {
        background: #E4F0FF;
        color: #2F80ED;
    }

    .today-value {
        font-weight: 700;
        font-size: 15px;
    }

    .today-label {
        font-size: 12px;
        color: var(--muted);
    }

    .btn-view-more {
        margin-top: 14px;
        width: 100%;
        background: #F5F7F6;
        border: none;
        border-radius: 10px;
        padding: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
    }

    /* ---- donut ---- */
    .donut-holder {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 6px auto 14px;
    }

    .donut-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 22px;
        font-weight: 700;
        text-align: center;
        color: var(--ink);
    }

    .donut-center span {
        display: block;
        font-size: 10px;
        font-weight: 500;
        color: var(--muted);
    }

    .donut-stats {
        display: flex;
        gap: 8px;
    }

    .donut-stat {
        flex: 1;
        background: #F8F9F8;
        border-radius: 10px;
        padding: 8px 4px;
    }

    .donut-stat-label {
        font-size: 11px;
        color: var(--muted);
    }

    .donut-stat-value {
        font-size: 14px;
        font-weight: 700;
    }

    /* ---- department list ---- */
    .dept-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }

    .dept-row:last-child {
        border-bottom: none;
    }

    .dept-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .bg-soft-rose {
        background: #FCE9EC;
        color: #E0556B;
    }

    .bg-soft-pink {
        background: #FDE9F3;
        color: #E0559C;
    }

    .dept-text {
        flex: 1;
        min-width: 0;
    }

    .dept-name {
        font-weight: 700;
        font-size: 13px;
    }

    .dept-sub {
        font-size: 11px;
        color: var(--muted);
    }

    .dept-percent {
        font-weight: 700;
        font-size: 13px;
    }

    /* ================= admin dashboard additions ================= */
    .stat-sub {
        font-size: 11px;
        color: var(--muted);
        margin: -6px 0 10px;
    }

    .chart-holder.h-220 {
        height: 220px;
    }

    .chart-holder.h-260 {
        height: 260px;
    }

    .dot-blue {
        background: #2F80ED;
    }

    .month-input {
        width: 150px;
        border-radius: 10px;
    }

    /* finance summary */
    .money-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid var(--border);
    }

    .money-row:last-child {
        border-bottom: none;
    }

    .money-label {
        font-size: 12px;
        color: var(--muted);
    }

    .money-value {
        font-size: 18px;
        font-weight: 700;
    }

    .money-value.in {
        color: var(--primary-color);
    }

    .money-value.out {
        color: #E0455A;
    }

    /* today's patients table */
    .panel-scroll {
        overflow-x: auto;
    }

    .mini-table {
        width: 100%;
        font-size: 12.5px;
    }

    .mini-table th {
        font-size: 11px;
        font-weight: 600;
        color: var(--muted);
        padding: 6px 8px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .mini-table td {
        padding: 8px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    .mini-table tbody tr:last-child td {
        border-bottom: none;
    }

    .visit-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 12px;
        background: #F0F2F1;
        color: var(--ink);
    }

    .visit-badge.vt-new {
        background: #E4F0FF;
        color: #2F80ED;
    }

    .visit-badge.vt-mf {
        background: #EEE9FE;
        color: #7C4DFF;
    }

    .visit-badge.vt-bf {
        background: #FDEFD9;
        color: #C77A12;
    }

    .visit-badge.vt-fu {
        background: #E4F7EC;
        color: #1BA860;
    }

    .visit-badge.vt-refill {
        background: #E1F6EF;
        color: #14A97F;
    }

    .visit-badge.vt-discharge {
        background: #FDE9EA;
        color: #E0455A;
    }

    .status-pill {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 9px;
        border-radius: 12px;
    }

    .status-pill.paid {
        background: #E4F7EC;
        color: #1BA860;
    }

    .status-pill.pending {
        background: #FDE9EA;
        color: #E0455A;
    }

    .status-pill.none {
        color: var(--muted);
    }

    /* alert lists */
    .alert-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
    }

    .alert-item {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 12.5px;
        padding: 7px 0;
        border-bottom: 1px solid var(--border);
    }

    .alert-item:last-child {
        border-bottom: none;
    }
</style>
@stop

@section('js')
@parent
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
    const finance = @json($finance);
    const patientSeries = @json($patientSeries);
    const occupancyVal = {{ $occupancyPercent }};
    const paymentMix = @json($paymentMix);
    const visitTypeStats = @json($visitTypeStats);
    const diseaseStats = @json($diseaseStats);

    $(document).ready(function () {

        // ----- income / expense -----
        const ieChart = new Chart(document.getElementById('incomeExpenseChart'), {
            type: 'line',
            data: {
                labels: finance.yearly.labels,
                datasets: [
                    {
                        label: 'ចំណូល', data: finance.yearly.income, borderColor: '#006D36',
                        backgroundColor: 'rgba(0,109,54,0.08)', tension: .4, fill: true, pointRadius: 4, borderWidth: 2
                    },
                    {
                        label: 'ចំណាយ', data: finance.yearly.expense, borderColor: '#E0559C',
                        backgroundColor: 'rgba(224,85,156,0.06)', tension: .4, fill: true, pointRadius: 4, borderWidth: 2
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: c => c.dataset.label + ': $' +
                                Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { display: false, beginAtZero: true }
                },
                maintainAspectRatio: false
            }
        });

        $('.income-range-btn').on('click', function () {
            $('.income-range-btn').removeClass('active');
            $(this).addClass('active');
            const set = finance[$(this).data('range')];
            ieChart.data.labels = set.labels;
            ieChart.data.datasets[0].data = set.income;
            ieChart.data.datasets[1].data = set.expense;
            ieChart.update();
        });

        // ----- occupancy donut (unchanged) -----
        new Chart(document.getElementById('occupancyChart'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [occupancyVal, Math.max(0, 100 - occupancyVal)],
                    backgroundColor: ['#006D36', '#E7F4EC'], borderWidth: 0
                }]
            },
            options: { cutout: '78%', plugins: { legend: { display: false }, tooltip: { enabled: true } } }
        });

        // ----- new registered patients -----
        const pChart = new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: patientSeries.weekly.labels,
                datasets: [{
                    label: 'អ្នកជំងឺថ្មី', data: patientSeries.weekly.data,
                    backgroundColor: 'rgba(0,109,54,0.12)', borderColor: '#006D36',
                    borderWidth: 2, borderRadius: 6, maxBarThickness: 40
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { display: false, beginAtZero: true }
                },
                maintainAspectRatio: false
            }
        });

        $('.patient-range-btn').on('click', function () {
            $('.patient-range-btn').removeClass('active');
            $(this).addClass('active');
            const set = patientSeries[$(this).data('range')];
            pChart.data.labels = set.labels;
            pChart.data.datasets[0].data = set.data;
            pChart.update();
        });

        // ================= admin dashboard additions =================
        const money = v => '$' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 });
        const palette = ['#006D36', '#2F80ED', '#E0559C', '#F2994A', '#7C4DFF', '#14A97F', '#9AA5A0'];

        // ----- payment methods (this month) -----
        const pmLabels = { cash: 'សាច់ប្រាក់', khqr: 'KHQR', card: 'កាត' };
        const pmKeys = Object.keys(paymentMix);
        const pmCanvas = document.getElementById('paymentChart');
        if (pmCanvas && pmKeys.length) {
            new Chart(pmCanvas, {
                type: 'doughnut',
                data: {
                    labels: pmKeys.map(k => pmLabels[k] || k),
                    datasets: [{ data: pmKeys.map(k => paymentMix[k]), backgroundColor: palette, borderWidth: 0 }]
                },
                options: {
                    cutout: '65%', maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: c => c.label + ': ' + money(c.parsed) } }
                    }
                }
            });
        }

        // ----- visit types (selected month) -----
        const vtCanvas = document.getElementById('visitTypeChart');
        if (vtCanvas) {
            new Chart(vtCanvas, {
                type: 'doughnut',
                data: {
                    labels: visitTypeStats.labels,
                    datasets: [{ data: visitTypeStats.data, backgroundColor: palette, borderWidth: 0 }]
                },
                options: {
                    cutout: '65%', maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
                }
            });
        }

        // ----- disease statistics by sex (selected month) -----
        const dzCanvas = document.getElementById('diseaseChart');
        if (dzCanvas) {
            new Chart(dzCanvas, {
                type: 'bar',
                data: {
                    labels: diseaseStats.labels,
                    datasets: [
                        { label: 'ប្រុស', data: diseaseStats.male, backgroundColor: '#2F80ED', borderRadius: 4, maxBarThickness: 22 },
                        { label: 'ស្រី', data: diseaseStats.female, backgroundColor: '#E0559C', borderRadius: 4, maxBarThickness: 22 }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        $('#diseaseMonth').on('change', function () {
            if (this.value) {
                window.location = window.location.pathname + '?month=' + encodeURIComponent(this.value);
            }
        });
    });
</script>
@stop
