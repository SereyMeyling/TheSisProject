@extends('adminlte::page')

@section('title', 'Pharmacy - Sell')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title">
        <i class="fas fa-cash-register"></i>
        ឱសថស្ថាន - លក់ថ្នាំ
    </h2>
    <a href="{{ route('pharmacy.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-boxes"></i> ទៅកាន់ស្តុក
    </a>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card">
            <div class="card-header"><strong>ព័ត៌មានលក់</strong></div>
            <div class="card-body">
                <form id="sellForm">
                    @csrf
                    <div class="alert alert-danger d-none" id="sellErrors"></div>
                    <div class="form-group">
                        <label>លេខសម្គាល់អ្នកជំងឺ (ទុកទទេប្រសិនបើអតិថិជនចរណ៍)</label>
                        <input type="number" name="patient_id" class="form-control" placeholder="Patient ID">
                    </div>
                    <table class="table table-sm" id="sellItemsTable">
                        <thead>
                            <tr>
                                <th>ថ្នាំ</th>
                                <th style="width:120px">ចំនួន (គ្រាប់)</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="sellItemsBody">
                            <tr>
                                <td><select class="form-control sell-medicine" required></select></td>
                                <td><input type="number" class="form-control sell-qty" min="1" required></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAddSellRow">
                        <i class="fas fa-plus"></i> បន្ថែមថ្នាំ
                    </button>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>សរុប: $<span id="sellTotalPreview">0.00</span></strong>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-cash-register"></i> លក់</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><strong>ការលក់ថ្មីៗ</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ថ្ងៃខែ</th>
                            <th>អតិថិជន</th>
                            <th class="text-right">សរុប</th>
                            <th class="text-center">PDF</th>
                        </tr>
                    </thead>
                    <tbody id="saleHistoryBody">
                        <tr><td colspan="4" class="text-center text-muted">កំពុងផ្ទុក...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/pharmacy.css') }}">
@stop

@section('js')
<script>
    const routes = {
        sellSearch: "{{ route('pharmacy.sell.search') }}",
        sellStore: "{{ route('pharmacy.sell.store') }}",
        sellHistory: "{{ route('pharmacy.sell.history') }}",
    };
    const csrf = "{{ csrf_token() }}";
</script>
<script src="{{ asset('js/pharmacy-sell.js') }}"></script>
@stop
