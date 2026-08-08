@extends('adminlte::page')

@section('title', 'Billing & Invoice Management')

@section('content')

<div class="page-header-row mb-4">
    <div>
        <h2 class="page-title mb-0">ការគ្រប់គ្រងការទូទាត់ប្រាក់ និងវិក្កយបត្រ</h2>
        <p class="page-subtitle mb-0">Billing &amp; Invoice Management</p>
    </div>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCreateInvoice">
        <i class="fas fa-plus mr-1"></i> បង្កើតវិក្កយបត្រថ្មី (Create Invoice)
    </button>
</div>

{{-- Success Toast Notification --}}
<div id="billingSuccessToast" class="alert alert-success alert-dismissible fade show d-none mb-3" role="alert">
    <i class="fas fa-check-circle mr-2"></i><span id="billingSuccessToastMessage"></span>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<!-- Stats Overview Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon icon-neutral">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div>
                <span class="stat-label">វិក្កយបត្រសរុប (Total Invoices)</span>
                <h3 id="statTotalInvoices">{{ $totalInvoices }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon icon-positive">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div>
                <span class="stat-label">ចំណូលទទួលបានសរុប (Total Revenue)</span>
                <h3 class="text-value-positive" id="statTotalRevenue">${{ number_format($totalRevenue, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon icon-negative">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div>
                <span class="stat-label">ប្រាក់ជំពាក់សរុប (Total Unpaid Balance)</span>
                <h3 class="text-value-negative" id="statTotalUnpaid">${{ number_format($totalUnpaid, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="toolbar flex-wrap justify-content-between">
            <div class="d-flex align-items-center flex-wrap toolbar-filters">
                <!-- Search Box -->
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" class="form-control border-0"
                        placeholder="ស្វែងរកវិក្កយបត្រ (លេខវិក្កយបត្រ, ឈ្មោះអ្នកជំងឺ)...">
                </div>

                {{-- Date Filter --}}
                <div class="form-group mb-0 d-flex align-items-center" style="gap: 6px;">
                    <input type="date" id="dateFrom" class="form-control" placeholder="From">
                    <span class="text-muted">-</span>
                    <input type="date" id="dateTo" class="form-control" placeholder="To">
                </div>

                <!-- Status Filter -->
                <div class="form-group mb-0">
                    <select id="statusFilter" class="form-control custom-select">
                        <option value="">-- ស្ថានភាពទាំងអស់ --</option>
                        <option value="unpaid">មិនទាន់បង់ (Unpaid)</option>
                        <option value="partial">បង់ខ្លះ (Partial)</option>
                        <option value="paid">បានទូទាត់រួច (Paid)</option>
                        <option value="cancelled">បានលុបចោល (Cancelled)</option>
                    </select>
                </div>

                <!-- Visit Type Filter -->
                <div class="form-group mb-0">
                    <select id="visitTypeFilter" class="form-control custom-select">
                        <option value="">-- ប្រភេទចូលពិនិត្យទាំងអស់ --</option>
                        <option value="opd">អ្នកជំងឺក្រៅ (OPD)</option>
                        <option value="ipd">អ្នកជំងឺសម្រាកព្យាបាល (IPD)</option>
                    </select>
                </div>

                {{-- Reset Filters Button --}}
                <button type="button" id="btnResetFilters" class="btn btn-outline-secondary" title="សម្អាតតម្រង (Reset Filters)">
                    <i class="fas fa-redo-alt"></i>
                </button>
            </div>

            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCreateInvoice" style="display: none">
                <i class="fas fa-plus mr-1"></i> បង្កើតវិក្កយបត្រថ្មី
            </button>
        </div>

        <div class="px-3 pb-3">
            <div id="billingTableContainer">
                @include('billing.partials.table')
            </div>
        </div>
    </div>
</div>

{{-- ── 1. CREATE INVOICE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalCreateInvoice" tabindex="-1" aria-labelledby="modalCreateInvoiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-clean">
                <h5 class="modal-title" id="modalCreateInvoiceLabel">
                    <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>បង្កើតវិក្កយបត្រថ្មី (Create Invoice)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createInvoiceForm" action="{{ route('billing.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="createInvoiceAlert" class="alert alert-danger d-none mb-3"></div>

                    <!-- Visit Type: OPD vs IPD -->
                    <div class="form-group mb-3">
                        <label class="field-label">ប្រភេទចូលពិនិត្យ (Visit Type) <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-secondary active" id="labelVisitOPD">
                                <input type="radio" name="visit_type" value="opd" checked autocomplete="off"> <i class="fas fa-walking mr-1"></i> OPD (អ្នកជំងឺក្រៅ)
                            </label>
                            <label class="btn btn-outline-primary" id="labelVisitIPD">
                                <input type="radio" name="visit_type" value="ipd" autocomplete="off"> <i class="fas fa-bed mr-1"></i> IPD (អ្នកជំងឺសម្រាកព្យាបាល)
                            </label>
                        </div>
                    </div>

                    <!-- Admission picker: shown only for IPD -->
                    <div class="form-group mb-3 d-none" id="admissionPickerWrap">
                        <label for="create_admission_id" class="field-label">ជ្រើសរើសការចូលសម្រាកព្យាបាល (Admission) <span class="text-danger">*</span></label>
                        <select id="create_admission_id" name="admission_id" class="form-control custom-select">
                            <option value="">-- ជ្រើសរើសការចូលសម្រាកព្យាបាល --</option>
                            @foreach($admissions as $adm)
                                <option value="{{ $adm->admission_id }}"
                                    data-patient-id="{{ $adm->patient_id }}"
                                    data-name="{{ optional($adm->patient)->full_name }}"
                                    data-phone="{{ optional($adm->patient)->phone }}">
                                    {{ optional($adm->patient)->full_name }} — Room {{ optional($adm->room)->room_number ?? '—' }}
                                    (Admitted {{ $adm->admission_date ? $adm->admission_date->format('d/m/Y') : '' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_create_admission_id"></div>
                    </div>

                    <!-- Patient Details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="select_patient_id" class="field-label">ជ្រើសរើសអ្នកជំងឺ (Existing Patient)</label>
                            <select id="select_patient_id" name="patient_id" class="form-control custom-select">
                                <option value="">-- ជ្រើសរើសអ្នកជំងឺ ឬបញ្ចូលឈ្មោះខាងក្រោម --</option>
                                @foreach($patients as $pt)
                                    <option value="{{ $pt->patient_id }}" data-name="{{ $pt->full_name }}" data-phone="{{ $pt->phone }}">
                                        {{ $pt->full_name }} ({{ $pt->patient_code }}) - {{ $pt->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_patient_name" class="field-label">ឈ្មោះអ្នកជំងឺ (Patient Full Name) <span class="text-danger">*</span></label>
                            <input type="text" name="patient_name" id="create_patient_name" class="form-control" placeholder="បញ្ចូលឈ្មោះអ្នកជំងឺ" required>
                            <div class="invalid-feedback" id="error_create_patient_name"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_patient_phone" class="field-label">លេខទូរស័ព្ទ (Phone Number)</label>
                            <input type="tel"  maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'')" inputmode="numeric" name="patient_phone" id="create_patient_phone" class="form-control" placeholder="012 345 678">
                        </div>
                        <div class="col-md-6">
                            <label for="create_notes" class="field-label">ចំណាំ (Notes)</label>
                            <input type="text" name="notes" id="create_notes" class="form-control" placeholder="កំណត់ចំណាំផ្សេងៗ">
                        </div>
                    </div>

                    <!-- Itemized Fee Builder -->
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="section-heading mb-0"><i class="fas fa-list mr-1"></i>បញ្ជីសេវាកម្ម / ឱសថ / ពិនិត្យ (Fee Items)</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInvoiceItem">
                            <i class="fas fa-plus mr-1"></i> បន្ថែមសេវា (Add Item)
                        </button>
                    </div>

                    <table class="table table-bordered table-sm mb-2" id="invoiceItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">ប្រភេទ (Type)</th>
                                <th>បរិយាយ (Description)</th>
                                <th style="width: 100px;">ចំនួន (Qty)</th>
                                <th style="width: 130px;">តម្លៃ ($)</th>
                                <th style="width: 130px;">សរុប ($)</th>
                                <th style="width: 45px;"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItemsTbody">
                            <!-- Initial Row -->
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][item_type]" class="form-control form-control-sm item-type" required>
                                        <option value="consultation">ពិគ្រោះជំងឺ (Consultation)</option>
                                        <option value="prescription">ថ្នាំពេទ្យ (Medicine)</option>
                                        <option value="lab_test">មន្ទីរពិសោធន៍ (Lab Test)</option>
                                        <option value="room">បន្ទប់សម្រាក (Room Fee)</option>
                                        <option value="other">ផ្សេងៗ (Other)</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][description]" class="form-control form-control-sm item-desc" placeholder="បរិយាយសេវា" value="ថ្លៃពិគ្រោះជំងឺទូទៅ" required>
                                </td>
                                <td>
                                    <input type="number" name="items[0][qty]" class="form-control form-control-sm item-qty text-center" value="1" min="1" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm item-price text-right" value="15.00" min="0" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm item-subtotal text-right bg-light" value="15.00" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="total-box">
                                <span class="total-label">ប្រាក់សរុប (Total Amount):</span>
                                <span class="total-value" id="createInvoiceGrandTotal">$15.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light border px-4" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitCreateInvoice">
                        <i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── 2. PROCESS PAYMENT MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalPayInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-clean">
                <h5 class="modal-title">
                    <i class="fas fa-cash-register mr-2 text-primary"></i>ទូទាត់ប្រាក់វិក្កយបត្រ (Process Payment)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="payInvoiceForm" method="POST">
                @csrf
                <input type="hidden" id="pay_invoice_id" name="invoice_id">
                <div class="modal-body p-4">
                    <div id="payInvoiceAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="summary-box mb-3">
                        <div class="summary-row">
                            <span class="text-muted">លេខវិក្កយបត្រ:</span>
                            <strong id="payInvoiceNumber" class="text-dark"></strong>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">អ្នកជំងឺ:</span>
                            <strong id="payPatientName"></strong>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">ប្រាក់សរុប:</span>
                            <strong id="payTotalAmount" class="text-dark"></strong>
                        </div>
                        <div class="summary-row summary-row-highlight">
                            <span class="font-weight-bold text-value-negative">ប្រាក់ជំពាក់នៅសល់ (Balance Due):</span>
                            <strong id="payBalanceAmount" class="text-value-negative h5 mb-0"></strong>
                        </div>
                    </div>

                    <!-- Payment Method Toggle -->
                    <div class="form-group mb-3">
                        <label class="field-label">វិធីសាស្ត្រទូទាត់ (Payment Method) <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active" id="labelMethodCash">
                                <input type="radio" name="payment_method" value="cash" checked autocomplete="off">
                                <i class="fas fa-money-bill-wave mr-1"></i> ប្រាក់សុទ្ធ (Cash)
                            </label>
                            <label class="btn btn-outline-primary" id="labelMethodKHQR">
                                <input type="radio" name="payment_method" value="khqr" autocomplete="off">
                                <i class="fas fa-qrcode mr-1"></i> KHQR Code
                            </label>
                            <label class="btn btn-outline-primary" id="labelMethodCard">
                                <input type="radio" name="payment_method" value="card" autocomplete="off">
                                <i class="fas fa-credit-card mr-1"></i> Card
                            </label>
                        </div>
                    </div>

                    <!-- KHQR Display Area (Shown when KHQR is selected) -->
                    <div id="khqrDisplayContainer" class="khqr-box d-none text-center">
                        <div id="khqrQrWrap" class="mb-2 d-flex justify-content-center align-items-center" style="min-height: 180px;">
                            <span class="text-muted small">ជ្រើសរើស KHQR ដើម្បីបង្កើត QR</span>
                        </div>
                        <div id="khqrBankInfo" class="small text-left mx-auto d-none" style="max-width: 260px;">
                            <div class="d-flex justify-content-between"><span class="text-muted">ធនាគារ:</span> <strong id="khqrBankName">-</strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">ឈ្មោះគណនី:</span> <strong id="khqrAccountName">-</strong></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">លេខគណនី:</span> <strong id="khqrAccountNumber">-</strong></div>
                        </div>
                        <small class="text-muted d-block mt-2">សូមប្រើប្រាស់ App ធនាគារដើម្បីស្កេនទូទាត់ប្រាក់ KHQR</small>
                        <div id="khqrStatusMessage" class="mt-2 small font-weight-bold"></div>
                    </div>

                    <!-- Amount Paid -->
                    <div class="form-group mb-3">
                        <label for="pay_amount" class="field-label">ចំនួនប្រាក់ត្រូវបង់ ($ Amount Paid) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control form-control-lg font-weight-bold" required>
                        </div>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="form-group mb-0">
                        <label for="pay_ref" class="field-label small text-muted">លេខកូដប្រតិបត្តិការ (Transaction Ref / optional)</label>
                        <input type="text" name="transaction_ref" id="pay_ref" class="form-control form-control-sm" placeholder="ឧ. TXN-98472">
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitPayInvoice">
                        <i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ការទូទាត់ (Confirm Payment)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── 3. PRINTABLE RECEIPT MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalReceiptInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-clean">
                <h5 class="modal-title">
                    <i class="fas fa-print mr-2 text-primary"></i>ប័ណ្ណទូទាត់ប្រាក់ (Invoice Receipt)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4" id="modalReceiptBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-light border" data-dismiss="modal">បិទ (Close)</button>
                <button type="button" class="btn btn-primary px-4" id="btnPrintReceipt">
                    <i class="fas fa-print mr-1"></i> បោះពុម្ពវិក្កយបត្រ (Print Receipt)
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── 4. VIEW INVOICE DETAIL MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalInvoiceDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-clean">
                <h5 class="modal-title">
                    <i class="fas fa-eye mr-2 text-primary"></i>ព័ត៌មានលម្អិតវិក្កយបត្រ (Invoice Detail)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                {{-- Cancelled banner --}}
                <div id="viewCancelledBanner" class="alert alert-secondary d-none mb-3">
                    <i class="fas fa-ban mr-1"></i> <strong>វិក្កយបត្រនេះត្រូវបានលុបចោល (CANCELLED)</strong>
                    <div class="small mt-1">មូលហេតុ (Reason): <span id="viewCancelReason"></span></div>
                    <div class="small">ដោយ (By): <span id="viewCancelledBy"></span> &bull; <span id="viewCancelledAt"></span></div>
                </div>

                {{-- Invoice Information --}}
                <h6 class="section-heading-muted ">ព័ត៌មានវិក្កយបត្រ (Invoice Information)</h6>
                <div class="row mb-2">
                    <div class="col-md-6"><span class="text-muted">លេខវិក្កយបត្រ:</span> <strong id="viewInvoiceNumber" class="text-dark"></strong></div>
                    <div class="col-md-6"><span class="text-muted">ស្ថានភាព:</span> <span id="viewInvoiceStatus"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><span class="text-muted">កាលបរិច្ឆេទ:</span> <strong id="viewInvoiceDate"></strong></div>
                    <div class="col-md-6"><span class="text-muted">បង្កើតដោយ (Created By):</span> <strong id="viewCreatedBy"></strong></div>
                </div>

                <hr>
                <h6 class="section-heading-muted">ព័ត៌មានអ្នកជំងឺ (Patient Information)</h6>
                <div class="row mb-2">
                    <div class="col-md-6"><span class="text-muted">លេខកូដអ្នកជំងឺ (Patient Code):</span> <strong id="viewPatientCode"></strong></div>
                    <div class="col-md-6"><span class="text-muted">ភេទ (Gender):</span> <strong id="viewPatientGender"></strong></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><span class="text-muted">អ្នកជំងឺ:</span> <strong id="viewPatientName"></strong></div>
                    <div class="col-md-6"><span class="text-muted">លេខទូរស័ព្ទ:</span> <strong id="viewPatientPhone"></strong></div>
                </div>

                <hr>
                <h6 class="section-heading-muted">ព័ត៌មានចូលពិនិត្យ (Visit Information)</h6>
                <div class="row mb-3">
                    <div class="col-md-4"><span class="text-muted">ប្រភេទ:</span> <span id="viewVisitType"></span></div>
                    <div class="col-md-4" id="viewAdmissionWrap"><span class="text-muted">លេខចូលសម្រាក:</span> <strong id="viewAdmissionNumber"></strong></div>
                    <div class="col-md-4" id="viewRoomWrap"><span class="text-muted">បន្ទប់:</span> <strong id="viewRoomNumber"></strong></div>
                </div>

                <hr>
                <h6 class="section-heading"><i class="fas fa-list mr-1"></i>ធាតុទូទាត់ (Billing Items)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-3">
                        <thead>
                            <tr>
                                <th>ធាតុ (Item)</th>
                                <th>ប្រភេទ (Category)</th>
                                <th class="text-center">ចំនួន (Qty)</th>
                                <th class="text-right">តម្លៃរាយ (Unit Price)</th>
                                <th class="text-right">សរុប (Amount)</th>
                            </tr>
                        </thead>
                        <tbody id="viewInvoiceItems"></tbody>
                    </table>
                </div>

                <div class="row justify-content-end mb-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between"><span>ប្រាក់សរុប (Total Amount):</span> <strong id="viewInvoiceTotal"></strong></div>
                        <div class="d-flex justify-content-between text-value-positive"><span>ប្រាក់បានបង់ (Paid Amount):</span> <strong id="viewInvoicePaid"></strong></div>
                        <div class="d-flex justify-content-between text-value-negative border-top pt-1 mt-1"><span>ប្រាក់ជំពាក់ (Balance):</span> <strong id="viewInvoiceBalance"></strong></div>
                    </div>
                </div>

                <hr>
                <h6 class="section-heading-muted">ព័ត៌មានទូទាត់ (Payment Information)</h6>
                <div id="viewPaymentsList" class="mb-3"></div>

                <hr>
                <h6 class="section-heading-muted">ចំណាំ (Notes)</h6>
                <p class="mb-0" id="viewNotes"></p>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-light border" data-dismiss="modal">បិទ (Close)</button>
            </div>
        </div>
    </div>
</div>

{{-- ── 5. EDIT INVOICE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalEditInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-clean">
                <h5 class="modal-title">
                    <i class="fas fa-pen mr-2 text-primary"></i>កែប្រែវិក្កយបត្រ (Edit Invoice)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editInvoiceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div id="editInvoiceAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_patient_name" class="field-label">ឈ្មោះអ្នកជំងឺ (Patient Full Name) <span class="text-danger">*</span></label>
                            <input type="text" name="patient_name" id="edit_patient_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_patient_phone" class="field-label">លេខទូរស័ព្ទ (Phone Number)</label>
                            <input type="tel" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'')" inputmode="numeric" name="patient_phone" id="edit_patient_phone" class="form-control">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_notes" class="field-label">ចំណាំ (Notes)</label>
                        <input type="text" name="notes" id="edit_notes" class="form-control">
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="section-heading mb-0"><i class="fas fa-list mr-1"></i>បញ្ជីសេវាកម្ម / ឱសថ / ពិនិត្យ (Fee Items)</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddEditInvoiceItem">
                            <i class="fas fa-plus mr-1"></i> បន្ថែមសេវា (Add Item)
                        </button>
                    </div>

                    <table class="table table-bordered table-sm mb-2" id="editInvoiceItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">ប្រភេទ (Type)</th>
                                <th>បរិយាយ (Description)</th>
                                <th style="width: 100px;">ចំនួន (Qty)</th>
                                <th style="width: 130px;">តម្លៃ ($)</th>
                                <th style="width: 130px;">សរុប ($)</th>
                                <th style="width: 45px;"></th>
                            </tr>
                        </thead>
                        <tbody id="editInvoiceItemsTbody"></tbody>
                    </table>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="total-box">
                                <span class="total-label">ប្រាក់សរុប (Total Amount):</span>
                                <span class="total-value" id="editInvoiceGrandTotal">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light border px-4" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitEditInvoice">
                        <i class="fas fa-save mr-1"></i> រក្សាទុកការកែប្រែ (Save Changes)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── 6. CANCEL INVOICE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalCancelInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-clean modal-header-danger">
                <h5 class="modal-title">
                    <i class="fas fa-ban mr-2"></i>លុបចោលវិក្កយបត្រ (Cancel Invoice)
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="cancelInvoiceForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="cancelInvoiceAlert" class="alert alert-danger d-none mb-3"></div>
                    <p>អ្នកជិតលុបចោលវិក្កយបត្រលេខ <strong id="cancelInvoiceNumber" class="text-dark"></strong>។ សកម្មភាពនេះមិនអាចត្រឡប់ក្រោយបានទេ ប៉ុន្តែកំណត់ត្រានឹងត្រូវបានរក្សាទុកសម្រាប់សវនកម្ម។</p>
                    <p class="text-muted small">(You are about to cancel this invoice. It will not be deleted — the record is kept for audit purposes.)</p>
                    <div class="form-group mb-0">
                        <label for="cancel_reason" class="field-label">មូលហេតុនៃការលុបចោល (Cancellation Reason) <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="ឧ. Duplicate invoice, patient data entry error..."></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">បិទ (Close)</button>
                    <button type="submit" class="btn btn-danger px-4" id="btnSubmitCancelInvoice">
                        <i class="fas fa-ban mr-1"></i> បញ្ជាក់ការលុបចោល (Confirm Cancel)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@stop

@section('js')
@parent
{{-- JavaScript for the Billing module --}}
<script>
    window.billingConfig = {
        baseUrl: "{{ url('billing') }}",
        indexUrl: "{{ route('billing.index') }}",
        storeUrl: "{{ route('billing.store') }}",
        generateKhqrUrl: "{{ route('payment.generateKhqr') }}",
        checkKhqrStatusUrlBase: "{{ url('payment/check-status') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/billing.js') }}"></script>
@stop