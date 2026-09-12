@extends('adminlte::page')

@section('title', 'វេជ្ជបណ្ឌិត (Doctor Consultation & Directory)')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Kantumruuy+Pro:wght@400;500;600;700&display=swap');

    :root {
        --primary-green: #006D36;
        --primary-hover: #005429;
        --accent-blue: #2563EB;
        --accent-rose: #E0556B;
        --bg-light: #F8FAFC;
        --card-border: #E2E8F0;
        --text-dark: #0F172A;
        --text-muted: #64748B;
    }

    body, .content-wrapper {
        background-color: var(--bg-light) !important;
        font-family: 'Inter', 'Kantumruuy Pro', sans-serif !important;
    }

    /* Modern Card Styles */
    .card-modern {
        background: #FFFFFF;
        border-radius: 16px;
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-header-custom {
        background: #FFFFFF;
        border-bottom: 1px solid #F1F5F9;
        padding: 16px 20px;
        border-radius: 16px 16px 0 0;
    }

    /* Profile Top Header */
    .doc-top-bar {
        background: linear-gradient(135deg, #006D36 0%, #004D26 100%);
        color: #FFFFFF;
        border-radius: 16px;
        padding: 20px 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 109, 54, 0.25);
    }

    .avatar-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .vitals-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px;
        text-align: center;
        transition: all 0.2s;
    }

    .vitals-box:hover {
        border-color: #CBD5E1;
        background: #FFFFFF;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    .vitals-val {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    /* Diagnosis Tags */
    .diag-tag {
        display: inline-block;
        background: #EFF6FF;
        color: #1E40AF;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        border: 1px solid #BFDBFE;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .diag-tag:hover, .diag-tag.selected {
        background: #2563EB;
        color: #FFFFFF;
        border-color: #2563EB;
    }

    /* Tabs Styling */
    .nav-tabs-custom .nav-link {
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 10px;
        margin-right: 6px;
    }

    .nav-tabs-custom .nav-link.active {
        background: var(--primary-green);
        color: #FFFFFF !important;
    }

    /* Action Buttons */
    .btn-action-lab {
        background: #2563EB;
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        padding: 12px 20px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
    }

    .btn-action-lab:hover {
        background: #1D4ED8;
        color: #FFFFFF;
    }

    .btn-action-admit {
        background: #FFFFFF;
        color: #475569;
        border: 1px solid #CBD5E1;
        border-radius: 12px;
        font-weight: 600;
        padding: 12px 20px;
    }

    .btn-action-admit:hover {
        background: #F1F5F9;
        color: #0F172A;
    }

    .btn-action-confirm {
        background: var(--primary-green);
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        padding: 12px 24px;
        box-shadow: 0 4px 14px rgba(0, 109, 54, 0.3);
    }

    .btn-action-confirm:hover {
        background: var(--primary-hover);
        color: #FFFFFF;
    }
</style>

<div class="container-fluid py-3">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-lg mb-4" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-lg mb-4" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- ======================================================================== --}}
    {{-- ROLE 1: DOCTOR CONSULTATION WORKSPACE                                    --}}
    {{-- ======================================================================== --}}
    @if($userRole === 'doctor')

        {{-- Top Bar Profile Header --}}
        <div class="doc-top-bar d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width: 50px; height: 50px; font-size: 22px;">
                    <i class="fas fa-user-md"></i>
                </div>
                <div>
                    <h5 class="font-weight-bold mb-0">{{ $doctorInfo['name'] }}</h5>
                    <small class="text-white-50"><i class="fas fa-hospital-alt mr-1"></i> {{ $doctorInfo['department'] }} | ID: {{ $doctorInfo['code'] }}</small>
                </div>
            </div>

            <div class="d-flex align-items-center mt-2 mt-md-0">
                <span class="badge badge-light px-3 py-2 font-weight-bold mr-3" style="border-radius: 10px; font-size: 13px;">
                    <i class="fas fa-circle text-success mr-1"></i> On Duty (សកម្ម)
                </span>
                <span class="text-white-50 small"><i class="far fa-calendar-alt mr-1"></i> {{ date('d M Y') }}</span>
            </div>
        </div>

        <div class="row">

            {{-- LEFT COLUMN: PATIENT CARD & VITALS & REASON --}}
            <div class="col-lg-4 mb-4">

                {{-- Patient Identity Card --}}
                <div class="card-modern p-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($activeRecord->patient->full_name ?? 'Patient') }}&background=006D36&color=fff&bold=true" class="avatar-circle mr-3" alt="Patient Avatar">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-1">{{ $activeRecord->patient->full_name ?? 'លោក ពាក់ មី' }}</h5>
                            <span class="badge badge-success px-2 py-1 font-weight-bold" style="border-radius: 6px;">{{ $activeRecord->patient->patient_code ?? 'ID-2001-0023' }}</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row text-muted small">
                        <div class="col-6 mb-2">
                            <span class="d-block text-secondary font-weight-bold">អាយុ / Age</span>
                            <span class="text-dark font-weight-bold">18 ឆ្នាំ (Years)</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="d-block text-secondary font-weight-bold">ភេទ / Gender</span>
                            <span class="text-dark font-weight-bold">{{ ($activeRecord->patient->sex ?? '') == 'Female' ? 'ស្រី (Female)' : 'ប្រុស (Male)' }}</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="d-block text-secondary font-weight-bold">ទូរស័ព្ទ / Phone</span>
                            <span class="text-dark">{{ $activeRecord->patient->phone ?? '012 345 678' }}</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="d-block text-secondary font-weight-bold">កាលបរិច្ឆេទ / Date</span>
                            <span class="text-dark">{{ $activeRecord->visit_date ? $activeRecord->visit_date->format('d/m/Y H:i') : date('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Vitals Box --}}
                <div class="card-modern p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-heartbeat text-danger mr-2"></i> សញ្ញាជីវិត (Vital Signs)</h6>
                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold px-2 py-1" data-toggle="modal" data-target="#vitalsModal" style="border-radius: 6px;">
                            <i class="fas fa-edit mr-1"></i> កែប្រែ (Edit)
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="vitals-box">
                                <small class="text-muted d-block font-weight-bold mb-1">សម្ពាធឈាម (BP)</small>
                                <div class="vitals-val text-primary">{{ $activeRecord->blood_pressure ?? '120/80' }} <small class="text-muted">mmHg</small></div>
                            </div>
                        </div>

                        <div class="col-6 mb-3">
                            <div class="vitals-box">
                                <small class="text-muted d-block font-weight-bold mb-1">ចង្វាក់បេះដូង (HR)</small>
                                <div class="vitals-val text-success">{{ $activeRecord->heart_rate ?? '75' }} <small class="text-muted">bpm</small></div>
                            </div>
                        </div>

                        <div class="col-6 mb-3">
                            <div class="vitals-box">
                                <small class="text-muted d-block font-weight-bold mb-1">កំដៅ (Temp)</small>
                                <div class="vitals-val text-danger">{{ $activeRecord->temperature ?? '38.6' }} <small class="text-muted">°C</small></div>
                            </div>
                        </div>

                        <div class="col-6 mb-3">
                            <div class="vitals-box">
                                <small class="text-muted d-block font-weight-bold mb-1">អុកស៊ីសែន (SPO2)</small>
                                <div class="vitals-val text-info">{{ $activeRecord->spo2 ?? $activeRecord->oxygen_saturation ?? '98' }} <small class="text-muted">%</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Chief Complaint / Reason Box --}}
                <div class="card-modern p-4">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-notes-medical text-warning mr-2"></i> មូលហេតុមកពិនិត្យ (Chief Complaint)</h6>
                    <p class="text-secondary small mb-0 bg-light p-3 rounded-lg border">
                        {{ $activeRecord->chief_complaint ?? 'អ្នកជំងឺមានអាការៈក្ដៅខ្លួន ឈឺបំពង់ក និងអស់កម្លាំង (Fever, Sore Throat & Fatigue) រយៈពេល ២ ថ្ងៃមកហើយ។' }}
                    </p>
                </div>

            </div>

            {{-- RIGHT COLUMN: TREATMENT NOTES, DIAGNOSIS & ACTIONS --}}
            <div class="col-lg-8 mb-4">
                <form action="{{ route('doctor.update', $activeRecord->record_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-modern p-4 mb-4">

                        {{-- Nav Tabs: Current Note & Past History --}}
                        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="consultTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="note-tab" data-toggle="tab" href="#note" role="tab"><i class="fas fa-edit mr-1"></i> កត់ត្រាការព្យាបាល (Current Note)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab"><i class="fas fa-history mr-1"></i> ប្រវត្តិព្យាបាលចាស់ (Past History)</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="consultTabContent">
                            {{-- Tab 1: Current Treatment Note --}}
                            <div class="tab-pane fade show active" id="note" role="tabpanel">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark">ចំណាំការព្យាបាលរបស់គ្រូពេទ្យ (Clinical Examination & Notes)</label>
                                    <textarea name="notes" class="form-control border-secondary-subtle p-3" rows="4" style="border-radius: 12px;" placeholder="បញ្ចូលចំណាំពិនិត្យ និងការព្យាបាលរបស់អ្នកជំងឺ...">{{ old('notes', $activeRecord->notes) }}</textarea>
                                </div>
                            </div>

                            {{-- Tab 2: Past History Timeline --}}
                            <div class="tab-pane fade" id="history" role="tabpanel">
                                <div class="p-2" style="max-height: 240px; overflow-y: auto;">
                                    @forelse($historyRecords as $hist)
                                        <div class="border-left border-success pl-3 pb-3 mb-2">
                                            <div class="d-flex justify-content-between">
                                                <strong class="text-dark small"><i class="far fa-calendar-alt mr-1"></i> {{ $hist->visit_date ? $hist->visit_date->format('d-m-Y H:i') : 'N/A' }}</strong>
                                                <span class="badge badge-light border">{{ $hist->status_destination ?? 'done' }}</span>
                                            </div>
                                            <p class="mb-1 text-primary font-weight-bold small">រោគវិនិច្ឆ័យ: {{ $hist->diagnosis ?? 'N/A' }}</p>
                                            <p class="mb-0 text-muted small">{{ $hist->notes }}</p>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center py-4 small">គ្មានប្រវត្តិព្យាបាលចាស់នៅក្នុងប្រព័ន្ធទេ។</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Tag-based Diagnosis Multi-Select Box --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">រោគវិនិច្ឆ័យ (Diagnosis) <span class="text-danger">*</span></label>
                            
                            {{-- Quick Diagnosis Preset Tags --}}
                            <div class="d-flex flex-wrap gap-2 mb-2" style="gap: 8px;">
                                <span class="diag-tag" onclick="addDiagTag('Acute Pharyngitis')">+ Acute Pharyngitis</span>
                                <span class="diag-tag" onclick="addDiagTag('Hypertension')">+ Hypertension</span>
                                <span class="diag-tag" onclick="addDiagTag('Common Cold')">+ Common Cold</span>
                                <span class="diag-tag" onclick="addDiagTag('Dengue Fever')">+ Dengue Fever</span>
                                <span class="diag-tag" onclick="addDiagTag('Acute Gastritis')">+ Acute Gastritis</span>
                            </div>

                            <input type="text" id="diagnosisInput" name="diagnosis" class="form-control p-3 font-weight-bold text-dark" style="border-radius: 12px;" value="{{ old('diagnosis', $activeRecord->diagnosis) }}" placeholder="បញ្ចូលរោគវិនិច្ឆ័យ ឬជ្រើសរើស Tag ខាងលើ..." required>
                        </div>

                        {{-- Pharmacy Instruction Note Box --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark"><i class="fas fa-pills text-success mr-1"></i> វេជ្ជបញ្ជាថ្នាំ (Pharmacy Instructions)</label>
                            <textarea name="prescription_notes" class="form-control p-3" rows="3" style="border-radius: 12px;" placeholder="បញ្ចូលឈ្មោះថ្នាំ កម្រិតប្រើប្រាស់ និងការណែនាំសម្រាប់ឱសថការី...">{{ old('prescription_notes', $activeRecord->prescription_notes) }}</textarea>
                        </div>

                        {{-- ACTION BUTTONS BOTTOM ROW --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-2" style="gap: 12px;">
                            <div class="d-flex flex-wrap" style="gap: 10px;">
                                {{-- Refer to Lab Test --}}
                                <button type="button" class="btn btn-action-lab" data-toggle="modal" data-target="#labModal">
                                    <i class="fas fa-vials mr-1"></i> Refer to Lab Test
                                </button>

                                {{-- Admit as Inpatient --}}
                                <button type="button" class="btn btn-action-admit" data-toggle="modal" data-target="#admitModal">
                                    <i class="fas fa-bed mr-1"></i> Admit as Inpatient
                                </button>
                            </div>

                            {{-- Confirm & Prescribe --}}
                            <button type="submit" name="status_destination" value="pharmacy" class="btn btn-action-confirm">
                                <i class="fas fa-check-circle mr-1"></i> Confirm & Prescribe
                            </button>
                        </div>

                    </div>
                </form>
            </div>

        </div>

        {{-- MODAL 1: REFER TO LAB TEST --}}
        <div class="modal fade" id="labModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 rounded-lg shadow-lg">
                    <form action="{{ route('doctor.lab-order.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="record_id" value="{{ $activeRecord->record_id }}">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-flask mr-2"></i> បញ្ជូនទៅពិនិត្យមន្ទីរពិសោធន៍ (Refer to Lab Test)</h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>

                        <div class="modal-body p-4">
                            <p class="text-muted small mb-3">សូមជ្រើសរើសតេស្តមន្ទីរពិសោធន៍ដែលត្រូវពិនិត្យសម្រាប់អ្នកជំងឺ <strong>{{ $activeRecord->patient->full_name ?? '' }}</strong>៖</p>

                            <div class="list-group" style="max-height: 260px; overflow-y: auto;">
                                @forelse($labTests as $test)
                                    <label class="list-group-item d-flex justify-content-between align-items-center cursor-pointer border-radius-sm">
                                        <div>
                                            <input type="checkbox" name="test_ids[]" value="{{ $test->test_id }}" class="mr-2">
                                            <strong>{{ $test->test_name }}</strong>
                                        </div>
                                        <span class="badge badge-light border">${{ number_format($test->price, 2) }}</span>
                                    </label>
                                @empty
                                    <label class="list-group-item"><input type="checkbox" name="test_ids[]" value="1" class="mr-2"> CBC (Complete Blood Count)</label>
                                    <label class="list-group-item"><input type="checkbox" name="test_ids[]" value="2" class="mr-2"> Blood Sugar Test (Fasting)</label>
                                    <label class="list-group-item"><input type="checkbox" name="test_ids[]" value="3" class="mr-2"> Urine Routine & Microscopy</label>
                                    <label class="list-group-item"><input type="checkbox" name="test_ids[]" value="4" class="mr-2"> COVID-19 Rapid Antigen Test</label>
                                @endforelse
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">បោះបង់</button>
                            <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-paper-plane mr-1"></i> បញ្ជូនទៅ Lab (Submit)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL 2: ADMIT AS INPATIENT --}}
        <div class="modal fade" id="admitModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 rounded-lg shadow-lg">
                    <form action="{{ route('doctor.admit.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $activeRecord->patient_id }}">

                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-procedures mr-2"></i> បញ្ចូលសម្រាកព្យាបាល (Admit as Inpatient)</h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">អ្នកជំងឺ (Patient):</label>
                                <input type="text" class="form-control bg-light" value="{{ $activeRecord->patient->full_name ?? '' }} ({{ $activeRecord->patient->patient_code ?? '' }})" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">ជ្រើសរើសបន្ទប់ទំនេរ (Available Room) <span class="text-danger">*</span></label>
                                <select name="room_id" class="form-control font-weight-bold" required>
                                    <option value="">-- ជ្រើសរើសបន្ទប់ --</option>
                                    @forelse($availableRooms as $rm)
                                        <option value="{{ $rm->room_id }}">បន្ទប់ {{ $rm->room_number }} (ប្រភេទ: {{ $rm->room_type }} - ${{ number_format($rm->price_per_day, 2) }}/ថ្ងៃ)</option>
                                    @empty
                                        <option value="1">បន្ទប់ 101 (Private Room - $50.00/day)</option>
                                        <option value="2">បន្ទប់ 102 (General Ward - $25.00/day)</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">បោះបង់</button>
                            <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> បញ្ចូលសម្រាក (Confirm Admit)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL 3: EDIT VITALS --}}
        <div class="modal fade" id="vitalsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 rounded-lg shadow-lg">
                    <form action="{{ route('doctor.vitals.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="record_id" value="{{ $activeRecord->record_id }}">

                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-heartbeat mr-2"></i> ធ្វើបច្ចុប្បន្នភាពសញ្ញាជីវិត (Update Vitals)</h5>
                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label class="font-weight-bold text-dark small">សម្ពាធឈាម (BP):</label>
                                    <input type="text" name="blood_pressure" class="form-control" value="{{ $activeRecord->blood_pressure ?? '120/80' }}" placeholder="120/80">
                                </div>
                                <div class="col-6 form-group">
                                    <label class="font-weight-bold text-dark small">ចង្វាក់បេះដូង (bpm):</label>
                                    <input type="number" name="heart_rate" class="form-control" value="{{ $activeRecord->heart_rate ?? '75' }}">
                                </div>
                                <div class="col-6 form-group">
                                    <label class="font-weight-bold text-dark small">កំដៅ (°C):</label>
                                    <input type="number" step="0.1" name="temperature" class="form-control" value="{{ $activeRecord->temperature ?? '38.6' }}">
                                </div>
                                <div class="col-6 form-group">
                                    <label class="font-weight-bold text-dark small">អុកស៊ីសែន (%):</label>
                                    <input type="number" name="spo2" class="form-control" value="{{ $activeRecord->spo2 ?? $activeRecord->oxygen_saturation ?? '98' }}">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">បោះបង់</button>
                            <button type="submit" class="btn btn-danger font-weight-bold"><i class="fas fa-save mr-1"></i> រក្សាទុក (Save Vitals)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    {{-- ======================================================================== --}}
    {{-- ROLE 2: ADMIN DOCTOR DIRECTORY & CONSULTATION AUDIT                      --}}
    {{-- ======================================================================== --}}
    @elseif($userRole === 'admin')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold text-dark mb-1"><i class="fas fa-user-md text-success mr-2"></i> គ្រប់គ្រង និងត្រួតពិនិត្យវេជ្ជបណ្ឌិត (Doctors Directory Audit)</h3>
                <small class="text-muted">បញ្ជីវេជ្ជបណ្ឌិត និងការពិនិត្យតាមដានបន្ទុកការងារ (Administrative Overview)</small>
            </div>
        </div>

        {{-- Stat Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card-modern p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold d-block">វេជ្ជបណ្ឌិតសរុប (Total Doctors)</small>
                            <h3 class="font-weight-bold text-dark mb-0">{{ $totalDoctors ?? 0 }}</h3>
                        </div>
                        <span class="bg-light text-primary rounded-circle p-3"><i class="fas fa-user-md fa-2x"></i></span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-modern p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold d-block">វេជ្ជបណ្ឌិតកំពុងសកម្ម (Active Doctors)</small>
                            <h3 class="font-weight-bold text-success mb-0">{{ $activeDoctors ?? 0 }}</h3>
                        </div>
                        <span class="bg-light text-success rounded-circle p-3"><i class="fas fa-check-circle fa-2x"></i></span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-modern p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted font-weight-bold d-block">ការពិនិត្យសរុបថ្ងៃនេះ (Consultations Today)</small>
                            <h3 class="font-weight-bold text-info mb-0">{{ $todayConsultations ?? 0 }}</h3>
                        </div>
                        <span class="bg-light text-info rounded-circle p-3"><i class="fas fa-stethoscope fa-2x"></i></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Doctors Table --}}
        <div class="card-modern p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-list mr-1"></i> បញ្ជីឈ្មោះវេជ្ជបណ្ឌិតក្នុងប្រព័ន្ធ</h6>

                <form method="GET" action="{{ route('doctor.index') }}" class="form-inline">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="ស្វែងរកឈ្មោះ ឬកូដ...">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th>កូដបុគ្គលិក</th>
                            <th>ឈ្មោះវេជ្ជបណ្ឌិត</th>
                            <th>ដេប៉ាតឺម៉ង់</th>
                            <th>ជំនាញ (Specialization)</th>
                            <th>លេខទូរស័ព្ទ</th>
                            <th>ស្ថានភាព</th>
                            <th class="text-center">សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $doc)
                            <tr>
                                <td><span class="badge badge-light border font-weight-bold">{{ $doc->employee_code }}</span></td>
                                <td><strong class="text-dark">{{ $doc->full_name }}</strong></td>
                                <td>{{ $doc->department->department_name ?? 'General Clinic' }}</td>
                                <td><span class="badge badge-info">{{ $doc->specialization ?? 'General Physician' }}</span></td>
                                <td>{{ $doc->phone ?? '-' }}</td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-secondary" disabled><i class="fas fa-lock mr-1"></i> Read-only Audit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">មិនមានទិន្នន័យវេជ្ជបណ្ឌិតទេ។</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {!! $doctors->links('pagination::bootstrap-4') !!}
            </div>
        </div>

    {{-- ======================================================================== --}}
    {{-- ROLE 3: NURSE TRIAGE & VITALS PREPARATION QUEUE                          --}}
    {{-- ======================================================================== --}}
    @elseif($userRole === 'nurse')

        <div class="doc-top-bar d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h5 class="font-weight-bold mb-0"><i class="fas fa-user-nurse mr-2"></i> {{ $nurseShift['name'] ?? 'Nurse Workspace' }}</h5>
                <small class="text-white-50"><i class="fas fa-clock mr-1"></i> {{ $nurseShift['shift'] }} | {{ $nurseShift['department'] }}</small>
            </div>
            <span class="badge badge-warning text-dark font-weight-bold px-3 py-2" style="border-radius: 10px;">
                <i class="fas fa-user-md mr-1"></i> Doctor Assigned: {{ $nurseShift['assigned_doctor'] }}
            </span>
        </div>

        {{-- Queue Table for Nurse Vitals Preparation --}}
        <div class="card-modern p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-heartbeat text-danger mr-2"></i> បញ្ជីអ្នកជំងឺត្រៀមវាស់សញ្ញាជីវិត (Triage Queue)</h6>
                <span class="badge badge-danger px-3 py-1 font-weight-bold">{{ $pendingVitalsCount ?? 0 }} Pending Vitals</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th>កូដ</th>
                            <th>ឈ្មោះអ្នកជំងឺ</th>
                            <th>សម្ពាធឈាម</th>
                            <th>ចង្វាក់បេះដូង</th>
                            <th>កំដៅ</th>
                            <th>SPO2</th>
                            <th class="text-center">សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($triageQueue as $rec)
                            <tr>
                                <td><span class="badge badge-light border font-weight-bold">{{ $rec->patient->patient_code ?? 'N/A' }}</span></td>
                                <td><strong class="text-dark">{{ $rec->patient->full_name ?? 'N/A' }}</strong></td>
                                <td>{{ $rec->blood_pressure ?? 'N/A' }}</td>
                                <td>{{ $rec->heart_rate ? $rec->heart_rate . ' bpm' : 'N/A' }}</td>
                                <td>{{ $rec->temperature ? $rec->temperature . ' °C' : 'N/A' }}</td>
                                <td>{{ $rec->spo2 ? $rec->spo2 . ' %' : 'N/A' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold" data-toggle="modal" data-target="#nurseVitalsModal{{ $rec->record_id }}">
                                        <i class="fas fa-edit mr-1"></i> បញ្ចូល Vitals
                                    </button>
                                </td>
                            </tr>

                            {{-- Nurse Vitals Modal for each record --}}
                            <div class="modal fade" id="nurseVitalsModal{{ $rec->record_id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 rounded-lg">
                                        <form action="{{ route('doctor.vitals.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="record_id" value="{{ $rec->record_id }}">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title font-weight-bold">បញ្ចូលសញ្ញាជីវិតសម្រាប់: {{ $rec->patient->full_name ?? '' }}</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <div class="modal-body p-4 text-left">
                                                <div class="row">
                                                    <div class="col-6 form-group">
                                                        <label class="small font-weight-bold">សម្ពាធឈាម (BP):</label>
                                                        <input type="text" name="blood_pressure" class="form-control" value="{{ $rec->blood_pressure }}" placeholder="120/80">
                                                    </div>
                                                    <div class="col-6 form-group">
                                                        <label class="small font-weight-bold">ចង្វាក់បេះដូង (bpm):</label>
                                                        <input type="number" name="heart_rate" class="form-control" value="{{ $rec->heart_rate }}">
                                                    </div>
                                                    <div class="col-6 form-group">
                                                        <label class="small font-weight-bold">កំដៅ (°C):</label>
                                                        <input type="number" step="0.1" name="temperature" class="form-control" value="{{ $rec->temperature }}">
                                                    </div>
                                                    <div class="col-6 form-group">
                                                        <label class="small font-weight-bold">អុកស៊ីសែន (%):</label>
                                                        <input type="number" name="spo2" class="form-control" value="{{ $rec->spo2 }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                                                <button type="submit" class="btn btn-danger font-weight-bold"><i class="fas fa-save mr-1"></i> រក្សាទុក Vitals</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">គ្មានអ្នកជំងឺរង់ចាំត្រួតពិនិត្យទេ។</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {!! $triageQueue->links('pagination::bootstrap-4') !!}
            </div>
        </div>

    @endif

</div>

@stop

@section('js')
@parent
<script>
    function addDiagTag(text) {
        var input = document.getElementById('diagnosisInput');
        if (!input) return;
        var current = input.value.trim();
        if (current.length > 0) {
            if (!current.includes(text)) {
                input.value = current + ', ' + text;
            }
        } else {
            input.value = text;
        }
    }
</script>
@stop