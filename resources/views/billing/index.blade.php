@extends('adminlte::page')

@section('title', 'Billing & Invoice Management')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0 mt-3">ការគ្រប់គ្រងការទូទាត់ប្រាក់ និងវិក្កយបត្រ (Billing Management)</h2>
    <button type="button" class="btn btn-success mt-3" data-toggle="modal" data-target="#modalCreateInvoice">
        <i class="fas fa-plus-circle mr-1"></i> បង្កើតវិក្កយបត្រថ្មី (Create Invoice)
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
            <div class="icon bg-light-primary">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div>
                <small class="text-muted">វិក្កយបត្រសរុប (Total Invoices)</small>
                <h3 id="statTotalInvoices">{{ $totalInvoices }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-light-success">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div>
                <small class="text-muted">ចំណូលទទួលបានសរុប (Total Revenue)</small>
                <h3 class="text-success" id="statTotalRevenue">${{ number_format($totalRevenue, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-light-danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div>
                <small class="text-muted">ប្រាក់ជំពាក់សរុប (Total Unpaid Balance)</small>
                <h3 class="text-danger" id="statTotalUnpaid">${{ number_format($totalUnpaid, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="toolbar flex-wrap justify-content-between p-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search Box -->
                <div class="search-box mr-2">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" class="form-control border-0"
                        placeholder="ស្វែងរកវិក្កយបត្រ (លេខវិក្កយបត្រ, ឈ្មោះអ្នកជំងឺ)...">
                </div>

                <!-- Status Filter -->
                <div class="form-group mb-0">
                    <select id="statusFilter" class="form-control custom-select">
                        <option value="">-- ស្ថានភាពទាំងអស់ (All Statuses) --</option>
                        <option value="unpaid">មិនទាន់បង់ (Unpaid)</option>
                        <option value="partial">បង់ខ្លះ (Partial)</option>
                        <option value="paid">បានទូទាត់រួច (Paid)</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalCreateInvoice">
                <i class="fas fa-plus-circle mr-1"></i> បង្កើតវិក្កយបត្រថ្មី
            </button>
        </div>

        <div class="container-fluid px-3">
            <div id="billingTableContainer">
                @include('billing.partials.table')
            </div>
        </div>
    </div>
</div>

{{-- ── 1. CREATE INVOICE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalCreateInvoice" tabindex="-1" aria-labelledby="modalCreateInvoiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-success text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title font-weight-bold" id="modalCreateInvoiceLabel">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>បង្កើតវិក្កយបត្រថ្មី (Create Invoice)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createInvoiceForm" action="{{ route('billing.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="createInvoiceAlert" class="alert alert-danger d-none mb-3"></div>

                    <!-- Patient Details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="select_patient_id" class="font-weight-bold">ជ្រើសរើសអ្នកជំងឺ (Existing Patient)</label>
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
                            <label for="create_patient_name" class="font-weight-bold">ឈ្មោះអ្នកជំងឺ (Patient Full Name) <span class="text-danger">*</span></label>
                            <input type="text" name="patient_name" id="create_patient_name" class="form-control" placeholder="បញ្ចូលឈ្មោះអ្នកជំងឺ" required>
                            <div class="invalid-feedback" id="error_create_patient_name"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_patient_phone" class="font-weight-bold">លេខទូរស័ព្ទ (Phone Number)</label>
                            <input type="text" name="patient_phone" id="create_patient_phone" class="form-control" placeholder="012 345 678">
                        </div>
                        <div class="col-md-6">
                            <label for="create_notes" class="font-weight-bold">ចំណាំ (Notes)</label>
                            <input type="text" name="notes" id="create_notes" class="form-control" placeholder="កំណត់ចំណាំផ្សេងៗ">
                        </div>
                    </div>

                    <!-- Itemized Fee Builder -->
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold mb-0 text-primary"><i class="fas fa-list mr-1"></i>បញ្ជីសេវាកម្ម / ឱសថ / ពិនិត្យ (Fee Items)</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddInvoiceItem">
                            <i class="fas fa-plus mr-1"></i> បន្ថែមសេវា (Add Item)
                        </button>
                    </div>

                    <table class="table table-bordered table-sm mb-2" id="invoiceItemsTable">
                        <thead class="bg-light">
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
                            <div class="p-2 bg-light rounded text-right border">
                                <span class="font-weight-bold mr-2">ប្រាក់សរុប (Total Amount):</span>
                                <span class="h4 text-success font-weight-bold mb-0" id="createInvoiceGrandTotal">$15.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between bg-light">
                    <button type="button" class="btn btn-light border px-4" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitCreateInvoice">
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
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-cash-register mr-2"></i>ទូទាត់ប្រាក់វិក្កយបត្រ (Process Payment)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="payInvoiceForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="payInvoiceAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="p-3 bg-light rounded mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">លេខវិក្កយបត្រ:</span>
                            <strong id="payInvoiceNumber" class="text-primary"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">អ្នកជំងឺ:</span>
                            <strong id="payPatientName"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">ប្រាក់សរុប:</span>
                            <strong id="payTotalAmount" class="text-dark"></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-1 mt-1">
                            <span class="font-weight-bold text-danger">ប្រាក់ជំពាក់នៅសល់ (Balance Due):</span>
                            <strong id="payBalanceAmount" class="text-danger h5 mb-0"></strong>
                        </div>
                    </div>

                    <!-- Payment Method Toggle -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">វិធីសាស្ត្រទូទាត់ (Payment Method) <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-success active" id="labelMethodCash">
                                <input type="radio" name="payment_method" value="cash" checked autocomplete="off">
                                <i class="fas fa-money-bill-wave mr-1"></i> ប្រាក់សុទ្ធ (Cash)
                            </label>
                            <label class="btn btn-outline-primary" id="labelMethodKHQR">
                                <input type="radio" name="payment_method" value="khqr" autocomplete="off">
                                <i class="fas fa-qrcode mr-1"></i> KHQR Code
                            </label>
                        </div>
                    </div>

                    <!-- KHQR Display Area (Shown when KHQR is selected) -->
                    <div id="khqrDisplayContainer" class="text-center p-3 mb-3 border rounded bg-white d-none" style="box-shadow: inset 0 0 10px rgba(0,0,0,0.03);">
                        <img src="{{ asset('images/aba_khqr.jpg') }}" alt="ABA KHQR Code" class="img-fluid rounded mb-2 border p-1" style="max-width: 180px;" onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=ABA_ACCOUNT_010596402';">
                        <div>
                            <span class="badge badge-danger px-3 py-1 font-weight-bold">BAKONG KHQR</span>
                        </div>
                        <small class="text-muted d-block mt-1">សូមប្រើប្រាស់ App ធនាគារដើម្បីស្កេនទូទាត់ប្រាក់ KHQR</small>
                    </div>

                    <!-- Amount Paid -->
                    <div class="form-group mb-3">
                        <label for="pay_amount" class="font-weight-bold">ចំនួនប្រាក់ត្រូវបង់ ($ Amount Paid) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control form-control-lg font-weight-bold text-success" required>
                        </div>
                    </div>

                    <!-- Transaction Ref -->
                    <div class="form-group mb-0">
                        <label for="pay_ref" class="font-weight-bold small text-muted">លេខកូដប្រតិបត្តិការ (Transaction Ref / optional)</label>
                        <input type="text" name="transaction_ref" id="pay_ref" class="form-control form-control-sm" placeholder="ឧ. TXN-98472">
                    </div>
                </div>

                <div class="modal-footer justify-content-between bg-light">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitPayInvoice">
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
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-dark text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-print mr-2"></i>ប័ណ្ណទូទាត់ប្រាក់ (Invoice Receipt)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4" id="modalReceiptBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer justify-content-between bg-light">
                <button type="button" class="btn btn-light border" data-dismiss="modal">បិទ (Close)</button>
                <button type="button" class="btn btn-primary px-4" id="btnPrintReceipt">
                    <i class="fas fa-print mr-1"></i> បោះពុម្ពវិក្កយបត្រ (Print Receipt)
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .page-title { font-weight: 700; color: #222; }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 5px 18px rgba(0, 0, 0, .05);
    }
    .stat-card h3 { margin: 0; font-weight: 700; }
    .icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: flex; justify-content: center; align-items: center;
        font-size: 22px;
    }
    .bg-light-primary { background: #e7f1ff; color: #0d6efd; }
    .bg-light-success { background: #dff6e8; color: #18864b; }
    .bg-light-danger { background: #fde8e8; color: #dc3545; }

    .card {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .05);
    }
    .toolbar { display: flex; align-items: center; gap: 12px; }
    .search-box {
        display: flex; align-items: center;
        background: #f3f5f7; border-radius: 12px; padding: 0 15px; width: 320px;
    }
    .search-box input { background: none; box-shadow: none; }
    .search-box i { color: #888; }

    .table thead th { background: #f5f6f7; border: none; color: #555; font-size: 14px; }
    .table td { vertical-align: middle; border-top: 1px solid #eee; }

    .action-icons { display: flex; gap: 6px; }

    #billingTableContainer { position: relative; min-height: 120px; transition: opacity .15s ease; }
    #billingTableContainer.loading { opacity: .45; pointer-events: none; }

    /* Print styles */
    @media print {
        body * { visibility: hidden; }
        #receiptPrintArea, #receiptPrintArea * { visibility: visible; }
        #receiptPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>
@stop

@section('js')
@parent
<script>
    $(document).ready(function () {

        const $container = $('#billingTableContainer');
        const $search = $('#search');
        const $statusFilter = $('#statusFilter');
        let debounceTimer;

        function currentParams(page) {
            return {
                search: $search.val(),
                status: $statusFilter.val(),
                page: page || 1,
            };
        }

        function loadInvoices(page) {
            const params = currentParams(page);
            $container.addClass('loading');

            $.ajax({
                url: "{{ route('billing.index') }}",
                method: 'GET',
                data: params,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $container.html(res.html);
                    $('#statTotalInvoices').text(res.totalInvoices);
                    $('#statTotalRevenue').text('$' + res.totalRevenue);
                    $('#statTotalUnpaid').text('$' + res.totalUnpaid);

                    const qs = $.param(params);
                    history.replaceState(null, '', "{{ route('billing.index') }}?" + qs);
                },
                error: function () {
                    console.error('Failed to load invoice list.');
                },
                complete: function () {
                    $container.removeClass('loading');
                }
            });
        }

        // Filters
        $search.on('keyup', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { loadInvoices(1); }, 400);
        });

        $statusFilter.on('change', function () { loadInvoices(1); });

        $(document).on('click', '#billingTableContainer .pagination a', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
            loadInvoices(page);
        });

        // Existing patient selection auto-fill
        $('#select_patient_id').on('change', function () {
            const $opt = $(this).find(':selected');
            if ($opt.val()) {
                $('#create_patient_name').val($opt.data('name'));
                $('#create_patient_phone').val($opt.data('phone'));
            }
        });

        // Dynamic Invoice Item Rows
        let itemIndex = 1;
        $('#btnAddInvoiceItem').on('click', function () {
            const rowHtml = `
                <tr class="item-row">
                    <td>
                        <select name="items[${itemIndex}][item_type]" class="form-control form-control-sm item-type" required>
                            <option value="consultation">ពិគ្រោះជំងឺ (Consultation)</option>
                            <option value="prescription">ថ្នាំពេទ្យ (Medicine)</option>
                            <option value="lab_test">មន្ទីរពិសោធន៍ (Lab Test)</option>
                            <option value="room">បន្ទប់សម្រាក (Room Fee)</option>
                            <option value="other">ផ្សេងៗ (Other)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm item-desc" placeholder="បរិយាយសេវា" required>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][qty]" class="form-control form-control-sm item-qty text-center" value="1" min="1" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm item-price text-right" value="0.00" min="0" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-subtotal text-right bg-light" value="0.00" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
            $('#invoiceItemsTbody').append(rowHtml);
            itemIndex++;
            recalculateGrandTotal();
        });

        $(document).on('click', '.btn-remove-item', function () {
            if ($('#invoiceItemsTbody .item-row').length > 1) {
                $(this).closest('tr').remove();
                recalculateGrandTotal();
            }
        });

        $(document).on('input', '.item-qty, .item-price', function () {
            const $row = $(this).closest('tr');
            const qty = parseFloat($row.find('.item-qty').val()) || 0;
            const price = parseFloat($row.find('.item-price').val()) || 0;
            const subtotal = qty * price;
            $row.find('.item-subtotal').val(subtotal.toFixed(2));
            recalculateGrandTotal();
        });

        function recalculateGrandTotal() {
            let total = 0;
            $('#invoiceItemsTbody .item-row').each(function () {
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                const price = parseFloat($(this).find('.item-price').val()) || 0;
                total += (qty * price);
            });
            $('#createInvoiceGrandTotal').text('$' + total.toFixed(2));
        }

        // Submit Create Invoice
        $('#createInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitCreateInvoice');
            const $alert = $('#createInvoiceAlert');

            $form.find('.form-control, .custom-select').removeClass('is-invalid');
            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងរក្សាទុក...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)');
                    $('#modalCreateInvoice').modal('hide');
                    $form[0].reset();
                    recalculateGrandTotal();

                    loadInvoices(1);
                    $('#billingSuccessToastMessage').text(res.message || 'Invoice created successfully.');
                    $('#billingSuccessToast').removeClass('d-none');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> បង្កើតវិក្កយបត្រ (Save Invoice)');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        let msg = Object.values(errors).flat().join('<br>');
                        $alert.removeClass('d-none').html(msg);
                    } else {
                        $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'An error occurred.');
                    }
                }
            });
        });

        // Trigger Pay Now Modal
        $(document).on('click', '.btn-pay-now', function () {
            const id = $(this).data('id');
            const number = $(this).data('number');
            const patient = $(this).data('patient');
            const total = parseFloat($(this).data('total'));
            const paid = parseFloat($(this).data('paid'));
            const balance = parseFloat($(this).data('balance'));

            $('#payInvoiceNumber').text(number);
            $('#payPatientName').text(patient);
            $('#payTotalAmount').text('$' + total.toFixed(2));
            $('#payBalanceAmount').text('$' + balance.toFixed(2));
            $('#pay_amount').val(balance.toFixed(2));

            $('#payInvoiceForm').attr('action', "{{ url('billing') }}/" + id + "/pay");
            $('#modalPayInvoice').modal('show');
        });

        // Payment Method toggle handler
        $('input[name="payment_method"]').on('change', function () {
            if ($(this).val() === 'khqr') {
                $('#khqrDisplayContainer').removeClass('d-none');
            } else {
                $('#khqrDisplayContainer').addClass('d-none');
            }
        });

        // Submit Process Payment
        $('#payInvoiceForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSubmitPayInvoice');
            const $alert = $('#payInvoiceAlert');

            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងដំណើរការ...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ការទូទាត់ (Confirm Payment)');
                    $('#modalPayInvoice').modal('hide');

                    loadInvoices(1);
                    $('#billingSuccessToastMessage').text(res.message || 'Payment processed successfully.');
                    $('#billingSuccessToast').removeClass('d-none');
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> បញ្ជាក់ការទូទាត់ (Confirm Payment)');
                    $alert.removeClass('d-none').text(xhr.responseJSON?.message || 'Failed to process payment.');
                }
            });
        });

        // Trigger Receipt Modal
        $(document).on('click', '.btn-view-receipt', function () {
            const id = $(this).data('id');
            $('#modalReceiptBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
            $('#modalReceiptInvoice').modal('show');

            $.ajax({
                url: "{{ url('billing') }}/" + id,
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $('#modalReceiptBody').html(res.html);
                },
                error: function () {
                    $('#modalReceiptBody').html('<div class="alert alert-danger mb-0">ពុំអាចទាញយកទិន្នន័យវិក្កយបត្របានឡើយ។</div>');
                }
            });
        });

        // Print Receipt Button Handler
        $('#btnPrintReceipt').on('click', function () {
            window.print();
        });
    });
</script>
@stop
