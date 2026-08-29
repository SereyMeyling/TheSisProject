@extends('adminlte::page')

@section('title', 'គ្រប់គ្រងឱសថស្ថាន & ការលក់ (Pharmacy & POS)')

@section('content')

<div class="toast-container-custom" id="toastContainer"></div>

<div class="pharmacy-container">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h2 class="page-title mb-1">
                <i class="fas fa-prescription-bottle-alt text-primary mr-2"></i> គ្រប់គ្រងឱសថស្ថាន & ការលក់ (Pharmacy & POS)
            </h2>
            <small class="text-muted">គ្រប់គ្រងស្តុកថ្នាំ ពិនិត្យការផុតកំណត់ និងលក់ថ្នាំជូនអតិថិជន/អ្នកជំងឺ</small>
        </div>

        <div id="stockActionBtns" class="mt-2 mt-sm-0">
            <button class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#modalSupplier" style="border-radius: 10px; font-weight: 600;">
                <i class="fas fa-truck mr-1"></i> បន្ថែម Supplier
            </button>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate" style="border-radius: 10px; font-weight: 600;">
                <i class="fas fa-plus-circle mr-1"></i> បន្ថែមថ្នាំថ្មី
            </button>
        </div>
    </div>

    {{-- Custom Segmented Navigation Tabs --}}
    <div class="mb-4">
        <ul class="nav pharmacy-nav-tabs" id="pharmacyTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="stock-tab" data-toggle="tab" href="#stockPane" role="tab">
                    <i class="fas fa-boxes"></i> ស្តុកថ្នាំ (Medicine Stock)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="sell-tab" data-toggle="tab" href="#sellPane" role="tab">
                    <i class="fas fa-cash-register"></i> លក់ថ្នាំ (Point of Sale)
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        {{-- ================= STOCK TAB ================= --}}
        <div class="tab-pane fade show active" id="stockPane" role="tabpanel">

            {{-- Stat Cards Grid --}}
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-filter" data-filter="">
                        <div class="icon-box bg-purple-gradient">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div>
                            <small>ថ្នាំសរុប (Total Medicines)</small>
                            <h3 id="statTotalMedicine">{{ number_format($stats['totalMedicine']) }}</h3>
                            <a href="{{ route('pharmacy.export.names') }}" class="stat-card-link" onclick="event.stopPropagation()">
                                <i class="fas fa-file-excel"></i> ទាញយកឈ្មោះថ្នាំ
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-filter" data-filter="">
                        <div class="icon-box bg-emerald-gradient">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <small>តម្លៃស្តុកសរុប (Stock Value)</small>
                            <h3 id="statStockValue">${{ number_format($stats['stockValue'], 2) }}</h3>
                            <a href="{{ route('pharmacy.export.stockReport') }}" class="stat-card-link" onclick="event.stopPropagation()">
                                <i class="fas fa-file-excel"></i> របាយការណ៍ស្តុក
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-filter" data-filter="low_stock">
                        <div class="icon-box bg-amber-gradient">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <small>ស្តុកជិតអស់ (Low Stock)</small>
                            <h3 id="statLowStock">{{ number_format($stats['lowStock']) }}</h3>
                            <a href="{{ route('pharmacy.export') }}" class="stat-card-link" onclick="event.stopPropagation()">
                                <i class="fas fa-file-excel"></i> ទាញយកបញ្ជីស្តុកជិតអស់
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card stat-card-filter" data-filter="expiring">
                        <div class="icon-box bg-rose-gradient">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div>
                            <small>ជិតផុតកំណត់ (៣០ ថ្ងៃ)</small>
                            <h3 id="statExpiringSoon">{{ number_format($stats['expiringSoon']) }}</h3>
                            <a href="#" class="stat-card-link" id="btnExpiringDetail" onclick="event.stopPropagation()">
                                <i class="fas fa-list"></i> មើលលម្អិត
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Stock Table Card --}}
            <div class="card-modern">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
                        <div class="search-box flex-grow-1 max-w-md">
                            <i class="fas fa-search"></i>
                            <input type="text" id="stockSearch" class="form-control" placeholder="ស្វែងរកថ្នាំ (តាមឈ្មោះ, ប្រភេទ, កូដ NDC)...">
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small font-weight-bold text-muted">សម្គាល់ពណ៌៖</span>
                            <span class="legend-chip low-stock"><span class="dot"></span> ស្តុកជិតអស់</span>
                            <span class="legend-chip expiring"><span class="dot"></span> ជិតផុតកំណត់</span>
                            <span class="legend-chip both"><span class="dot"></span> ស្តុកជិតអស់ + ជិតផុតកំណត់</span>
                        </div>
                    </div>

                    <div id="activeFilterBar" class="d-none mb-3">
                        <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                            <span class="small font-weight-bold text-muted">កំពុងត្រង (Active Filter):</span>
                            <div id="activeFilterChips" class="d-flex flex-wrap" style="gap:6px"></div>
                            <button id="filterClearAll" type="button" class="btn btn-sm btn-outline-danger py-0 px-2 ml-auto" style="border-radius: 12px;">
                                <i class="fas fa-times mr-1"></i> សម្អាតទាំងអស់
                            </button>
                        </div>
                    </div>

                    <div id="departmentTableContainer">
                        @include('form.phamacy.table')
                    </div>
                </div>
            </div>
        </div>
        {{-- ================= END STOCK TAB ================= --}}


        {{-- ================= SELL TAB (POS) ================= --}}
        <div class="tab-pane fade" id="sellPane" role="tabpanel">
            <div class="row">
                {{-- Left POS Form --}}
                <div class="col-lg-7 mb-4">
                    <div class="card-modern">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-cash-register text-primary mr-2"></i> ព័ត៌មានលក់ថ្នាំ (Point of Sale Checkout)</span>
                            <span class="badge badge-light border"><i class="fas fa-user mr-1"></i> អតិថិជន/អ្នកជំងឺ</span>
                        </div>

                        <div class="card-body p-4">
                            <form id="sellForm">
                                @csrf
                                <div class="alert alert-danger d-none" id="sellErrors" style="border-radius: 10px;"></div>

                                <div class="form-group mb-4">
                                    <label class="font-weight-bold">លេខសម្គាល់អ្នកជំងឺ ឬកូដអ្នកជំងឺ (ទុកទទេប្រសិនបើជាអតិថិជនចរណ៍)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0"><i class="fas fa-user-circle text-muted"></i></span>
                                        </div>
                                        <input type="text" name="patient_id" class="form-control border-left-0" placeholder="បញ្ចូល Patient ID ឬ កូដអ្នកជំងឺ (ឧ. P-0001)">
                                    </div>
                                </div>

                                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-shopping-cart text-info mr-1"></i> បញ្ជីថ្នាំត្រូវលក់ (Items List)</h6>
                                
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered align-middle mb-0" id="sellItemsTable">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>ថ្នាំ (Select Medicine)</th>
                                                <th style="width: 140px;">ចំនួន (Qty)</th>
                                                <th style="width: 50px;" class="text-center"><i class="fas fa-trash-alt"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody id="sellItemsBody">
                                            <tr>
                                                <td>
                                                    <select class="form-control sell-medicine" required>
                                                        <option value="">-- កំពុងផ្ទុកបញ្ជីថ្នាំ... --</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control sell-qty" min="1" value="1" required>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" style="border-radius: 6px;">&times;</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="btnAddSellRow" style="border-radius: 8px; font-weight: 600;">
                                    <i class="fas fa-plus mr-1"></i> បន្ថែមមុខថ្នាំ (Add Item Row)
                                </button>

                                {{-- Checkout Summary Box --}}
                                <div class="checkout-summary-box d-flex flex-wrap justify-content-between align-items-center">
                                    <div>
                                        <small class="text-light opacity-75 d-block">តម្លៃសរុបត្រូវទូទាត់ (Total Amount)</small>
                                        <div class="total-price-display">$<span id="sellTotalPreview">0.00</span></div>
                                    </div>
                                    <button class="btn checkout-btn" type="submit">
                                        <i class="fas fa-cash-register mr-2"></i> បញ្ចប់ការលក់ (Complete Sale)
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Recent Sales History --}}
                <div class="col-lg-5 mb-4">
                    <div class="card-modern">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-history text-info mr-2"></i> ប្រវត្តិការលក់ថ្មីៗ (Recent Transactions)</span>
                            <span class="badge badge-success"><i class="fas fa-check-double mr-1"></i> Live History</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>កាលបរិច្ឆេទ</th>
                                            <th>អតិថិជន</th>
                                            <th class="text-right">សរុប ($)</th>
                                            <th class="text-center">វិក្កយបត្រ PDF</th>
                                        </tr>
                                    </thead>
                                    <tbody id="saleHistoryBody">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">កំពុងផ្ទុកប្រវត្តិ...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- ================= END SELL TAB ================= --}}
    </div>
</div>

{{-- ====== Create Medicine Modal ====== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i> បន្ថែមថ្នាំថ្មី (Add New Medicine)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="createErrors"></div>
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">ឈ្មោះថ្នាំ (Medicine Name) <span class="text-danger">*</span></label>
                            <input type="text" name="medicine_name" class="form-control" placeholder="ឧ. Amoxicillin" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">កូដជាតិថ្នាំ (NDC Code)</label>
                            <input type="text" name="ndc_code" class="form-control" placeholder="NDC 0000-0000-00">
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">កម្រិត (Strength / Form)</label>
                            <input type="text" name="strength" class="form-control" placeholder="ឧ. 500mg, 250mg/5ml">
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">ខ្នាត (Unit) <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" placeholder="ឧ. គ្រាប់, ដប, ទីប" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">តម្លៃលក់រាយ ($ Unit Price) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="selling_price" class="form-control" placeholder="ឧ. 0.50" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">កម្រិតប្រកាសស្តុកជិតអស់ (Reorder Level)</label>
                            <input type="number" min="0" name="reorder_level" class="form-control" value="20">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> រក្សាទុកថ្នាំ</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Supplier Modal ====== --}}
<div class="modal fade" id="modalSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-truck mr-2"></i> បន្ថែម Supplier ថ្មី</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <form id="supplierForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">ឈ្មោះក្រុមហ៊ុន/អ្នកផ្គត់ផ្គង់ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="ឧ. Pharma Corp Co., Ltd" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">លេខទូរស័ព្ទ</label>
                        <input type="text" name="phone" class="form-control" placeholder="ឧ. 012 345 678">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">អ៊ីមែល</label>
                        <input type="email" name="email" class="form-control" placeholder="supplier@example.com">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> រក្សាទុក Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Restock Modal ====== --}}
<div class="modal fade" id="modalRestock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-boxes mr-2"></i> បញ្ចូលស្តុកថ្នាំថ្មី (Restock Batch)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <form id="restockForm">
                @csrf
                <input type="hidden" id="restock_medicine_id" name="medicine_id">
                <div class="modal-body p-4">
                    <div class="alert alert-info">
                        <strong>ថ្នាំ៖ </strong><span id="restock_medicine_name" class="font-weight-bold"></span>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">លេខឡូតិ៍ថ្នាំ (Batch Number) <span class="text-danger">*</span></label>
                            <input type="text" name="batch_number" class="form-control" placeholder="ឧ. BATCH-2026-001" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">កាលបរិច្ឆេទផុតកំណត់ (Expiry Date) <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">ចំនួនបញ្ចូល (Quantity) <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="quantity_initial" class="form-control" placeholder="ឧ. 100" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="font-weight-bold">តម្លៃទិញចូល ($ Purchase Price)</label>
                            <input type="number" step="0.01" min="0" name="purchase_price" class="form-control" placeholder="ឧ. 0.30">
                        </div>
                        <div class="form-group col-md-12 mb-3">
                            <label class="font-weight-bold">អ្នកផ្គត់ផ្គង់ (Supplier)</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">-- ជ្រើសរើស Supplier --</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->supplier_id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-download mr-1"></i> បញ្ចូលស្តុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@push('css')
<style>
    .dataTables_wrapper .dataTables_paginate {
        float: none !important;
        text-align: center !important;
        margin-top: 12px;
    }
    .dataTables_wrapper .pagination {
        justify-content: center;
    }
    .dataTables_wrapper .pagination .page-link {
        border-radius: 8px !important;
        margin: 0 3px !important;
        color: #4f46e5 !important;
        border: 1px solid transparent !important;
    }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background: #4f46e5 !important;
        border-color: #4f46e5 !important;
        color: #fff !important;
    }
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="{{ asset('css/pharmacy.css') }}">
@endpush

@section('js')
<script>
    const csrf = "{{ csrf_token() }}";
    const routes = {
        data: "{{ route('pharmacy.data') }}",
        store: "{{ route('pharmacy.store') }}",
        editBase: "{{ url('pharmacy') }}",
        updateBase: "{{ url('pharmacy') }}",
        destroyBase: "{{ url('pharmacy') }}",
        restockBase: "{{ url('pharmacy') }}",
        expiringDetail: "{{ route('pharmacy.expiring.detail') }}",
        supplierStore: "{{ route('pharmacy.suppliers.store') }}",
        stats: "{{ route('pharmacy.stats') }}",
        detailsBase: "{{ url('pharmacy') }}",
        sellSearch: "{{ route('pharmacy.sell.search') }}",
        sellStore: "{{ route('pharmacy.sell.store') }}",
        sellHistory: "{{ route('pharmacy.sell.history') }}",
    };

    $(function () {
        const $stockActionBtns = $('#stockActionBtns');
        let sellScriptLoaded = false;
        
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                paginate: { previous: '‹', next: '›' }
            }
        });

        $(document).on('shown.bs.tab', '#sell-tab', function () {
            $stockActionBtns.hide();
            if (!sellScriptLoaded) {
                sellScriptLoaded = true;
                const s = document.createElement('script');
                s.src = "{{ asset('js/pharmacy-sell.js') }}";
                document.body.appendChild(s);
            }
        });

        $(document).on('shown.bs.tab', '#stock-tab', function () {
            $stockActionBtns.show();
        });

        let stockSearchTimer;
        $('#stockSearch').on('keyup', function () {
            clearTimeout(stockSearchTimer);
            const val = $(this).val();
            stockSearchTimer = setTimeout(function () {
                if ($.fn.DataTable.isDataTable('#medicineTable')) {
                    $('#medicineTable').DataTable().search(val).draw();
                }
            }, 400);
        });

        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') === 'sell' || window.location.hash === '#sellPane') {
            $('#sell-tab').tab('show');
        }
    });
</script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="{{ asset('js/pharmacy.js') }}"></script>
@stop
