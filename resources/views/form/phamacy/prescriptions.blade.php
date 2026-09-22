@extends('adminlte::page')

@section('title', 'គ្រប់គ្រងវេជ្ជបញ្ជា (Prescription Management)')

@section('content')
<style>
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .stat-card .icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-right: 15px;
    }
    .bg-light-primary { background: #e8f0fe; color: #1a73e8; }
    .bg-light-success { background: #e8f5e9; color: #2e7d32; }

    .toolbar {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 16px 20px;
        background: #fff;
        border-bottom: 1px solid #edf2f7;
    }
    .search-box {
        position: relative;
        flex: 1;
        min-width: 240px;
    }
    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
    }
    .search-box input {
        padding-left: 38px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .toast-custom {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 12px 20px;
        border-radius: 8px;
        color: #fff;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .toast-custom.success { background: #38a169; }
    .toast-custom.error { background: #e53e3e; }
    .modal-header-custom {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    .prescription-item-row {
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        border: 1px solid #e2e8f0;
    }
</style>

<div id="toastContainer"></div>

{{-- Top Header Section --}}
<div class="row mb-4 align-items-center mt-2">
    <div class="col-md-4 col-sm-6 mb-2">
        <div class="stat-card">
            <div class="icon bg-light-primary">
                <i class="fas fa-file-prescription"></i>
            </div>
            <div>
                <small class="text-muted d-block">វេជ្ជបញ្ជាសរុប (Total Prescriptions)</small>
                <h3 id="statTotal" class="m-0 font-weight-bold">{{ $totalPrescriptions }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-8 text-right">
        <a href="{{ route('pharmacy.index') }}" class="btn btn-outline-secondary mr-2">
            <i class="fas fa-pills mr-1"></i> ឱសថស្ថាន (Stock & Medicine)
        </a>
     
    </div>
</div>

{{-- Main Card Container --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="toolbar flex-wrap">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" class="form-control" placeholder="ស្វែងរកតាមឈ្មោះអ្នកជំងឺ, កូដ, ឈ្មោះថ្នាំ...">
            </div>
        </div>

        <div class="px-3 py-2">
            <div id="prescriptionTableContainer">
                @include('form.phamacy.partials.prescription_table')
            </div>
        </div>
    </div>
</div>



@stop

@section('js')
<script>
$(document).ready(function () {
    let itemIndex = 1;
    let debounceTimer;

    $('#btnAddRow').on('click', function () {
        let medicineOptions = `@foreach ($medicines as $med)<option value="{{ $med->medicine_id }}">{{ $med->medicine_name }} ({{ $med->unit }})</option>@endforeach`;
        let newRow = `
            <div class="prescription-item-row">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="small font-weight-bold">ឈ្មោះថ្នាំ (Medicine)</label>
                        <select name="items[${itemIndex}][medicine_id]" class="form-control form-control-sm" required>
                            <option value="">-- ជ្រើសរើសថ្នាំ --</option>
                            ${medicineOptions}
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">កម្រិតប្រើ (Dosage)</label>
                        <input type="text" name="items[${itemIndex}][dosage]" class="form-control form-control-sm" placeholder="ឧ. 1 គ្រាប់" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">ពិសារ (Frequency)</label>
                        <input type="text" name="items[${itemIndex}][frequency]" class="form-control form-control-sm" placeholder="ឧ. 3 ដង/ថ្ងៃ ក្រោយបាយ" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold">ចំនួនថ្ងៃ (Days)</label>
                        <input type="number" min="1" name="items[${itemIndex}][duration_days]" class="form-control form-control-sm" value="5" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">ចំនួនសរុប (Total Qty)</label>
                        <input type="number" min="1" name="items[${itemIndex}][quantity]" class="form-control form-control-sm" value="15" required>
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
        $('#itemsContainer').append(newRow);
        itemIndex++;
    });

    $(document).on('click', '.btn-remove-row', function () {
        $(this).closest('.prescription-item-row').remove();
    });

    function loadPrescriptions(page = 1) {
        const search = $('#search').val();
        $('#prescriptionTableContainer').css('opacity', '0.5');

        $.ajax({
            url: "{{ route('pharmacy.prescriptions.index') }}",
            method: 'GET',
            data: { page: page, search: search },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                $('#prescriptionTableContainer').html(res.html).css('opacity', '1');
                if (res.total !== undefined) $('#statTotal').text(res.total);
            },
            error: function () {
                $('#prescriptionTableContainer').css('opacity', '1');
            }
        });
    }

    $('#search').on('keyup', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            loadPrescriptions(1);
        }, 400);
    });

    $(document).on('click', '#prescriptionTableContainer .pagination a', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
        loadPrescriptions(page);
    });

    function showToast(message, type = 'success') {
        let toast = `<div class="toast-custom ${type}">${message}</div>`;
        $('#toastContainer').append(toast);

        setTimeout(function () {
            $('.toast-custom:first').fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    }

    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif

    @if(session('error'))
        showToast(@json(session('error')), 'error');
    @endif
});
</script>
@stop
