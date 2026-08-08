@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid dash-wrap">
    {{-- STAT CARDS --}}
    <div class="row stat-row">

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-violet"><i class="fas fa-user"></i></span>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 12%</span>
                </div>
                <div class="stat-label">អ្នកជំងឺសរុប</div>
                <div class="stat-value">1,240</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-blue"><i class="fas fa-user-plus"></i></span>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 3%</span>
                </div>
                <div class="stat-label">អ្នកជំងឺថ្មី</div>
                <div class="stat-value">48</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-pink"><i class="fas fa-pills"></i></span>
                    <span class="stat-trend down"><i class="fas fa-arrow-down"></i> 2%</span>
                </div>
                <div class="stat-label">ថ្នាំសរុប</div>
                <div class="stat-value">8,421</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-teal"><i class="fas fa-wallet"></i></span>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 18%</span>
                </div>
                <div class="stat-label">ចំណូលប្រចាំថ្ងៃ</div>
                <div class="stat-value">$124k</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-rose"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 5%</span>
                </div>
                <div class="stat-label">ចំណាយប្រចាំថ្ងៃ</div>
                <div class="stat-value">$84k</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="stat-icon icon-green"><i class="fas fa-hand-holding-usd"></i></span>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 24%</span>
                </div>
                <div class="stat-label">ប្រាក់ចំណេញ</div>
                <div class="stat-value">$40k</div>
                <button type="button" class="btn-export">នាំចេញ<br>របាយការណ៍ PDF</button>
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
                        <button type="button" class="range-btn active" data-range="yearly">Yearly</button>
                        <button type="button" class="range-btn" data-range="monthly">Monthly</button>
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
                        <div class="today-value">124</div>
                        <div class="today-label">ការណាត់ជួប</div>
                    </div>
                </div>
                <div class="today-row">
                    <span class="today-icon bg-soft-red"><i class="fas fa-notes-medical"></i></span>
                    <div class="today-text">
                        <div class="today-value">08</div>
                        <div class="today-label">ករណីបន្ទាន់</div>
                    </div>
                </div>
                <div class="today-row">
                    <span class="today-icon bg-soft-blue"><i class="fas fa-bed"></i></span>
                    <div class="today-text">
                        <div class="today-value">12</div>
                        <div class="today-label">ព្រួងគែ</div>
                    </div>
                </div>

                <button type="button" class="btn-view-more">មើលបន្ថែម</button>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="panel h-100 text-center d-flex flex-column">
                <h3 class="panel-title text-left mb-2">ចំនួនគ្រែ</h3>
                <div class="donut-holder">
                    <canvas id="occupancyChart" width="150" height="150"></canvas>
                    <div class="donut-center">78%<span>កំពុងប្រើ</span></div>
                </div>
                <div class="donut-stats mt-auto">
                    <div class="donut-stat">
                        <div class="donut-stat-label">សរុប</div>
                        <div class="donut-stat-value">6,540</div>
                    </div>
                    <div class="donut-stat">
                        <div class="donut-stat-label">នៅសល់</div>
                        <div class="donut-stat-value text-danger">12</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- BOTTOM ROW: weekly progress + department list --}}
    <div class="row">

        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">ការវិវត្តអ្នកជំងឺ</h3>
                <div class="chart-holder">
                    <canvas id="weeklyChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <h3 class="panel-title mb-3">អ្នកជំងឺនៅរដ្ឋបាលតាមផ្នែក</h3>

                <div class="dept-row">
                    <span class="dept-icon bg-soft-rose"><i class="fas fa-heartbeat"></i></span>
                    <div class="dept-text">
                        <div class="dept-name">Cardiology</div>
                        <div class="dept-sub">342 Patients Active</div>
                    </div>
                    <div class="dept-percent">42%</div>
                </div>

                <div class="dept-row">
                    <span class="dept-icon bg-soft-pink"><i class="fas fa-baby"></i></span>
                    <div class="dept-text">
                        <div class="dept-name">Pediatrics</div>
                        <div class="dept-sub">156 Patients Active</div>
                    </div>
                    <div class="dept-percent">24%</div>
                </div>

                <div class="dept-row">
                    <span class="dept-icon bg-soft-blue"><i class="fas fa-brain"></i></span>
                    <div class="dept-text">
                        <div class="dept-name">Neurology</div>
                        <div class="dept-sub">88 Patients Active</div>
                    </div>
                    <div class="dept-percent">18%</div>
                </div>
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

    body, .dash-wrap {
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

    .icon-violet { background: #EEE9FE; color: #7C4DFF; }
    .icon-blue   { background: #E4F0FF; color: #2F80ED; }
    .icon-pink   { background: #FDE9F3; color: #E0559C; }
    .icon-teal   { background: #E1F6EF; color: #14A97F; }
    .icon-rose   { background: #FCE9EC; color: #E0556B; }
    .icon-green  { background: var(--primary-soft); color: var(--primary-color); }

    .stat-trend {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .stat-trend.up   { background: #E4F7EC; color: #1BA860; }
    .stat-trend.down { background: #FDE9EA; color: #E0455A; }

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
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
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
    .dot-green { background: var(--primary-color); }
    .dot-pink  { background: #E0559C; }

    /* ---- today's summary ---- */
    .today-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }
    .today-row:last-of-type { border-bottom: none; }

    .today-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .bg-soft-green { background: var(--primary-soft); color: var(--primary-color); }
    .bg-soft-red   { background: #FDE9EA; color: #E0455A; }
    .bg-soft-blue  { background: #E4F0FF; color: #2F80ED; }

    .today-value { font-weight: 700; font-size: 15px; }
    .today-label { font-size: 12px; color: var(--muted); }

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

    .donut-stat-label { font-size: 11px; color: var(--muted); }
    .donut-stat-value { font-size: 14px; font-weight: 700; }

    /* ---- department list ---- */
    .dept-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }
    .dept-row:last-child { border-bottom: none; }

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

    .bg-soft-rose { background: #FCE9EC; color: #E0556B; }
    .bg-soft-pink { background: #FDE9F3; color: #E0559C; }

    .dept-text { flex: 1; min-width: 0; }
    .dept-name { font-weight: 700; font-size: 13px; }
    .dept-sub  { font-size: 11px; color: var(--muted); }
    .dept-percent { font-weight: 700; font-size: 13px; }
</style>
@stop

@section('js')
@parent
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
    $(document).ready(function () {

        // ----- income / expense (area line) -----
        const ieCtx = document.getElementById('incomeExpenseChart');
        const ieLabels = ['មករា','កុម្ភៈ','មីនា','មេសា','ឧសភា','មិថុនា'];
        const ieChart = new Chart(ieCtx, {
            type: 'line',
            data: {
                labels: ieLabels,
                datasets: [
                    {
                        label: 'ចំណូល',
                        data: [30, 45, 38, 52, 60, 74],
                        borderColor: '#006D36',
                        backgroundColor: 'rgba(0,109,54,0.08)',
                        tension: .4,
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 2
                    },
                    {
                        label: 'ចំណាយ',
                        data: [20, 25, 22, 30, 28, 34],
                        borderColor: '#E0559C',
                        backgroundColor: 'rgba(224,85,156,0.06)',
                        tension: .4,
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { display: false }
                },
                maintainAspectRatio: false
            }
        });

        $('.range-btn').on('click', function () {
            $('.range-btn').removeClass('active');
            $(this).addClass('active');
            // TODO: swap dataset based on $(this).data('range') via AJAX
        });

        // ----- occupancy donut -----
        new Chart(document.getElementById('occupancyChart'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [78, 22],
                    backgroundColor: ['#006D36', '#E7F4EC'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '78%',
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });

        // ----- weekly patient progress (stepped bar) -----
        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: ['WEEK 1', 'WEEK 2', 'WEEK 3', 'WEEK 4'],
                datasets: [{
                    data: [40, 55, 55, 78],
                    backgroundColor: 'rgba(0,109,54,0.12)',
                    borderColor: '#006D36',
                    borderWidth: 2,
                    borderRadius: 6,
                    barThickness: 60
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { display: false }
                },
                maintainAspectRatio: false
            }
        });

    });
</script>
@stop
