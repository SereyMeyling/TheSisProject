@extends('adminlte::page')

@section('title', 'Pharmacy - Stock')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title">
        <i class="fas fa-pills"></i>
        ឱសថស្ថាន - ស្តុក
    </h2>
    <div>
        <button class="btn btn-outline-secondary mr-2" data-toggle="modal" data-target="#modalSupplier">
            <i class="fas fa-truck"></i>
            បន្ថែម Supplier
        </button>
        <button class="btn btn-success btn-add" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus-circle"></i>
            បន្ថែមថ្នាំ
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card stat-card-filter" data-filter="">
            <div class="icon bg-success-light"><i class="fas fa-pills"></i></div>
            <div>
                <small>ថ្នាំសរុប</small>
                <h3 id="statTotalMedicine">{{ number_format($stats['totalMedicine']) }}</h3>
                <a href="{{ route('pharmacy.export.names') }}" class="stat-card-link" onclick="event.stopPropagation()">
                    <i class="fas fa-file-excel"></i> ទាញយកឈ្មោះថ្នាំ
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card stat-card-filter" data-filter="">
            <div class="icon bg-blue-light"><i class="fas fa-boxes"></i></div>
            <div>
                <small>តម្លៃស្តុក</small>
                <h3 id="statStockValue">${{ number_format($stats['stockValue'], 2) }}</h3>
                <a href="{{ route('pharmacy.export.stockReport') }}" class="stat-card-link"
                    onclick="event.stopPropagation()">
                    <i class="fas fa-file-excel"></i> ទាញយករបាយការណ៍ស្តុក
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card stat-card-filter" data-filter="low_stock">
            <div class="icon bg-warning-light"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <small>ស្តុកជិតអស់</small>
                <h3 id="statLowStock">{{ number_format($stats['lowStock']) }}</h3>
                <a href="{{ route('pharmacy.export') }}" class="stat-card-link">
                    <i class="fas fa-file-excel"></i>
                    ទាញយក ស្តុកជិតអស់
                </a>
            </div>

        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card stat-card-filter" data-filter="expiring">
            <div class="icon bg-danger-light"><i class="fas fa-calendar-times"></i></div>
            <div>
                <small>ជិតផុតកំណត់ក្នុងរយះពេល​៣០ថ្ងៃ</small>
                <h3 id="statExpiringSoon">{{ number_format($stats['expiringSoon']) }}</h3>
                <a href="#" class="stat-card-link" id="btnExpiringDetail" onclick="event.stopPropagation()">
                    <i class="fas fa-list"></i> មើលលម្អិត
                </a>
            </div>
        </div>
    </div>
</div>

@if($stats['lowStockList']->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-exclamation-triangle text-warning"></i> ថ្នាំដែលស្តុកជិតអស់</h6>
            <span class="badge badge-warning">{{ $stats['lowStockList']->count() }}</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>ឈ្មោះថ្នាំ</th>
                        <th class="text-right">ស្តុកនៅសល់</th>
                        <th class="text-right">កម្រិតជូនដំណឹង</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['lowStockList'] as $item)
                        <tr class="{{ $item->stock_total == 0 ? 'table-danger' : 'table-warning' }}">
                            <td>{{ $item->medicine_name }}</td>
                            <td class="text-right">{{ $item->stock_total }}</td>
                            <td class="text-right">{{ $item->reorder_level }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-3 card">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 mb-3 small">
            <span><span class="legend-dot" style="background:#ffc107"></span>ស្តុកជិតអស់ </span>
            <span><span class="legend-dot" style="background:#dc3545"></span>ជិតផុតកំណត់ </span>
            <span><span class="legend-dot" style="background:#6f42c1"></span>ស្តុកជិតអស់ + ជិតផុតកំណត់ </span>
        </div>
        <div id="activeFilterBar" class="d-none mb-3">
            <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                <span class="small text-muted">កំពុងត្រង:</span>
                <div id="activeFilterChips" class="d-flex flex-wrap" style="gap:6px"></div>
                <button id="filterClearAll" type="button" class="btn btn-sm btn-outline-danger py-0 px-2">
                    <i class="fas fa-times mr-1"></i> សម្អាតទាំងអស់
                </button>
            </div>
        </div>
        <div id="departmentTableContainer">
            @include('form.phamacy.table')
        </div>
    </div>
</div>

{{-- ====== Create Modal ====== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">បន្ថែមថ្មី</h5>
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="Close"><span>&times;</span></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="createErrors"></div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>ឈ្មោះថ្នាំ</label>
                            <input type="text" name="medicine_name" class="form-control" placeholder="e.g. Amoxicillin"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>លេខកូដជាតិនៃថ្នាំ</label>
                            <input type="text" name="ndc_code" class="form-control" placeholder="NDC 0000-0000-00">
                        </div>
                        <div class="form-group col-md-4">
                            <label>ប្រភេទ</label>
                            <select name="category" class="form-control" required>
                                <option value="">ជ្រើសរើសប្រភេទ</option>
                                <option value="ថ្នាំគ្រាប់">ថ្នាំគ្រាប់</option>
                                <option value="ថ្នាំទឹក">ថ្នាំទឹក</option>
                                <option value="ថ្នាំចាក់">ថ្នាំចាក់</option>
                                <option value="ថ្នាំសម្រាប់លាប">ថ្នាំសម្រាប់លាប</option>
                                <option value="ថ្នាំបំបាត់ការឈឺចាប់">ថ្នាំបំបាត់ការឈឺចាប់</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ឯកតាទិញចូល (box / vial / bottle...)</label>
                            <input type="text" name="unit" class="form-control" placeholder="box" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ទម្រង់ដូស</label>
                            <select name="dosage_unit" class="form-control" required>
                                <option value="">ជ្រើសរើស ដូស</option>
                                <option value="mg">mg</option>
                                <option value="ml">ml</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ចំនួន mg/ml</label>
                            <input type="text" name="strength" class="form-control" placeholder="500" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>គ្រាប់/ឯកតា (1 box = ? គ្រាប់)</label>
                            <input type="number" name="pieces_per_unit" min="1" class="form-control" value="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ចំនួនឯកតាទិញចូល (box)</label>
                            <input type="number" name="quantity_initial" min="0" class="form-control" placeholder="0"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>តម្លៃទិញ/ឯកតា ($)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>តម្លៃលក់/គ្រាប់ ($)</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>លេខបាច់ (Batch No.)</label>
                            <input type="text" name="batch_number" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ថ្ងៃផុតកំណត់</label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>កម្រិតជូនដំណឹងស្តុកទាប (គ្រាប់)</label>
                            <input type="number" name="reorder_level" class="form-control" value="20">
                        </div>
                        <div class="form-group col-md-4">
                            <label>អ្នកផ្គត់ផ្គង់</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">មិនកំណត់</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-dismiss="modal" type="button">បោះបង់</button>
                    <button class="btn btn-primary" type="submit">រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Edit Modal ====== --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">កែទិន្នន័យ</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="medicine_id" id="edit_medicine_id">
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="editErrors"></div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>ឈ្មោះថ្នាំ</label>
                            <input type="text" name="medicine_name" id="edit_medicine_name" class="form-control"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>លេខកូដជាតិនៃថ្នាំ</label>
                            <input type="text" name="ndc_code" id="edit_ndc_code" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>ប្រភេទ</label>
                            <select name="category" id="edit_category" class="form-control" required>
                                <option value="ថ្នាំគ្រាប់">ថ្នាំគ្រាប់</option>
                                <option value="ថ្នាំទឹក">ថ្នាំទឹក</option>
                                <option value="ថ្នាំចាក់">ថ្នាំចាក់</option>
                                <option value="ថ្នាំសម្រាប់លាប">ថ្នាំសម្រាប់លាប</option>
                                <option value="ថ្នាំបំបាត់ការឈឺចាប់">ថ្នាំបំបាត់ការឈឺចាប់</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ឯកតាទិញចូល</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ទម្រង់ដូស</label>
                            <select name="dosage_unit" id="edit_dosage_unit" class="form-control" required>
                                <option value="mg">mg</option>
                                <option value="ml">ml</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>ចំនួន mg/ml</label>
                            <input type="text" name="strength" id="edit_strength" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>គ្រាប់/ឯកតា</label>
                            <input type="number" name="pieces_per_unit" id="edit_pieces_per_unit" min="1"
                                class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>តម្លៃទិញ/ឯកតា ($)</label>
                            <input type="number" step="0.01" name="unit_price" id="edit_unit_price" class="form-control"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>តម្លៃលក់/គ្រាប់ ($)</label>
                            <input type="number" step="0.01" name="selling_price" id="edit_selling_price"
                                class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>កម្រិតជូនដំណឹងស្តុកទាប (គ្រាប់)</label>
                            <input type="number" name="reorder_level" id="edit_reorder_level" class="form-control">
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted mb-1">បាច់ស្តុកបច្ចុប្បន្ន (មិនអាចកែពីទីនេះ - ប្រើប៊ូតុង "បន្ថែមស្តុក")</p>
                    <div id="edit_batches_list" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary">រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Restock Modal ====== --}}
<div class="modal fade" id="modalRestock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">បន្ថែមស្តុក: <span id="restock_name"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="restockForm">
                @csrf
                <input type="hidden" name="medicine_id" id="restock_medicine_id">
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="restockErrors"></div>
                    <div class="alert alert-info d-none py-2 small" id="restockLastBatchHint"></div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>លេខបាច់ (Batch No.)</label>
                            <input type="text" name="batch_number" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>ចំនួនឯកតាទិញចូល</label>
                            <input type="number" name="quantity_initial" min="1" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>តម្លៃទិញ/ឯកតា ($)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>ថ្ងៃផុតកំណត់</label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>អ្នកផ្គត់ផ្គង់</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">មិនកំណត់</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">បោះបង់</button>
                    <button class="btn btn-primary" type="submit">រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== ExpiringDetail Modal ====== --}}

<div class="modal fade" id="modalExpiringDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-times text-danger"></i> ថ្នាំជិតផុតកំណត់ (៣០ថ្ងៃ)</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ល.ខ</th>
                            <th>ឈ្មោះថ្នាំ</th>
                            <th>លេខបាច់</th>
                            <th class="text-right">នៅសល់</th>
                            <th>ថ្ងៃផុតកំណត់</th>
                            <th class="text-right">នៅសល់ប៉ុន្មានថ្ងៃ</th>
                        </tr>
                    </thead>
                    <tbody id="expiringDetailBody"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">បិទ</button>
            </div>
        </div>
    </div>
</div>
{{-- ====== supplier Modal ====== --}}
<div class="modal fade" id="modalSupplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">បន្ថែមអ្នកផ្គត់ផ្គង់</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="supplierForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="supplierErrors"></div>
                    <div class="form-group">
                        <label>ឈ្មោះអ្នកផ្គត់ផ្គង់</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>លេខទូរស័ព្ទ</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>អាសយដ្ឋាន</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">បោះបង់</button>
                    <button class="btn btn-primary" type="submit">រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Filter FAB ====== --}}
<div id="filterBackdrop" class="filter-backdrop d-none"></div>

<div id="filterFab" class="filter-fab">
    <i class="fas fa-filter"></i>
    <span id="filterBadge" class="filter-badge d-none">0</span>
</div>

<div id="filterPanel" class="filter-panel d-none">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="fas fa-sliders-h mr-1"></i> តម្រងទិន្នន័យ</h6>
        <button type="button" id="filterPanelClose" class="close" style="font-size:20px;">&times;</button>
    </div>
    <div class="form-group">
        <label class="small mb-1">ឈ្មោះថ្នាំ</label>
        <input type="text" id="filterName" class="form-control form-control-sm" placeholder="ស្វែងរកឈ្មោះ...">
    </div>
    <div class="form-group">
        <label class="small mb-1">តម្លៃ ($)</label>
        <div class="d-flex" style="gap:8px">
            <input type="number" step="0.01" id="filterPriceMin" class="form-control form-control-sm" placeholder="ពី">
            <input type="number" step="0.01" id="filterPriceMax" class="form-control form-control-sm" placeholder="ដល់">
        </div>
    </div>
    <div class="form-group">
        <label class="small mb-1">ស្តុក</label>
        <select id="filterStock" class="form-control form-control-sm">
            <option value="">ទាំងអស់</option>
            <option value="normal">ធម្មតា</option>
            <option value="low">ជិតអស់</option>
            <option value="out">អស់ស្តុក</option>
        </select>
    </div>
    <div class="form-group mb-3">
        <label class="small mb-1">ជិតផុតកំណត់ក្នុងរយៈពេល</label>
        <select id="filterExpiry" class="form-control form-control-sm">
            <option value="">ទាំងអស់</option>
            <option value="7">៧ ថ្ងៃ</option>
            <option value="15">១៥ ថ្ងៃ</option>
            <option value="30">៣០ ថ្ងៃ</option>
        </select>
    </div>
    <div class="d-flex justify-content-between">
        <button id="filterClear" type="button" class="btn btn-sm btn-light">សម្អាត</button>
        <button id="filterApply" type="button" class="btn btn-sm btn-primary">
            <i class="fas fa-check mr-1"></i> ត្រង
        </button>
    </div>
</div>

@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="{{ asset('css/pharmacy.css') }}">
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
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
    };
</script>
<script src="{{ asset('js/pharmacy.js') }}"></script>

@stop
