@extends('adminlte::page')

@section('title', 'គ្រប់គ្រងបុគ្គលិក (Employee Management)')

@section('content')
<style>
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
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
    .bg-light-info { background: #e0f7fa; color: #00838f; }
    .bg-light-success { background: #e8f5e9; color: #2e7d32; }
    .bg-light-warning { background: #fff8e1; color: #f57f17; }
    
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
    .search-box input:focus {
        background: #fff;
        border-color: #3182ce;
        box-shadow: 0 0 0 3px rgba(49,130,206,0.1);
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
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: white;
    }
    .modal-header-edit {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: white;
    }
</style>

<div id="toastContainer"></div>

{{-- Top Stats Section --}}
<div class="row mb-4 align-items-center mt-2">
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="stat-card">
            <div class="icon bg-light-primary">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <small class="text-muted d-block">បុគ្គលិកសរុប</small>
                <h3 id="statTotalEmployees" class="m-0 font-weight-bold">{{ $totalEmployees }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="stat-card">
            <div class="icon bg-light-info">
                <i class="fas fa-user-md"></i>
            </div>
            <div>
                <small class="text-muted d-block">វេជ្ជបណ្ឌិត</small>
                <h3 id="statTotalDoctors" class="m-0 font-weight-bold">{{ $totalDoctors }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="stat-card">
            <div class="icon bg-light-success">
                <i class="fas fa-user-nurse"></i>
            </div>
            <div>
                <small class="text-muted d-block">គិលានុបដ្ឋាក</small>
                <h3 id="statTotalNurses" class="m-0 font-weight-bold">{{ $totalNurses }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="stat-card">
            <div class="icon bg-light-warning">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <small class="text-muted d-block">បុគ្គលិកសកម្ម</small>
                <h3 id="statActiveEmployees" class="m-0 font-weight-bold">{{ $activeEmployees }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Main Card Container --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="toolbar flex-wrap">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" class="form-control" placeholder="ស្វែងរកតាមកូដ, ឈ្មោះ, ជំនាញ, លេខទូរស័ព្ទ...">
            </div>

            <div class="filter-box d-flex gap-2">
                <select id="filterRole" class="form-control" style="width: 160px; border-radius: 8px;">
                    <option value="">-- គ្រប់តួនាទីទាំងអស់ --</option>
                    <option value="doctor">វេជ្ជបណ្ឌិត (Doctor)</option>
                    <option value="nurse">គិលានុបដ្ឋាក (Nurse)</option>
                    <option value="pharmacist">ឱសថការី (Pharmacist)</option>
                    <option value="lab_technician">មន្ទីរពិសោធន៍ (Lab Tech)</option>
                    <option value="admin">អ្នកគ្រប់គ្រង (Admin)</option>
                </select>

                <select id="filterDepartment" class="form-control" style="width: 180px; border-radius: 8px;">
                    <option value="">-- គ្រប់ដេប៉ាតឺម៉ង់ --</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                    @endforeach
                </select>

                <select id="filterStatus" class="form-control" style="width: 140px; border-radius: 8px;">
                    <option value="">-- គ្រប់ស្ថានភាព --</option>
                    <option value="active">សកម្ម (Active)</option>
                    <option value="inactive">អសកម្ម (Inactive)</option>
                </select>
            </div>

            <button class="btn btn-primary ml-auto" data-toggle="modal" data-target="#modalCreate" style="border-radius: 8px;">
                <i class="fas fa-user-plus mr-1"></i> បន្ថែមបុគ្គលិក
            </button>
        </div>

        <div class="px-3 py-2">
            <div id="employeeTableContainer">
                @include('form.employee.partials.table')
            </div>
        </div>
    </div>
</div>

{{-- ====== Modal Create Employee ====== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2"></i> បន្ថែមបុគ្គលិកថ្មី (Add New Employee)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('employee.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">កូដបុគ្គលិក (Employee Code)</label>
                            <input type="text" name="employee_code" class="form-control" placeholder="ទុកទំនេរដើម្បីបង្កើតស្វ័យប្រវត្តិ (e.g. EMP-0001)">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ដេប៉ាតឺម៉ង់ (Department) <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-control" required>
                                <option value="">-- ជ្រើសរើសដេប៉ាតឺម៉ង់ --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">នាមត្រកូល / ឈ្មោះទី១ (First Name) <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" placeholder="ឧ. សុខ" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ឈ្មោះខ្លួន / ឈ្មោះទី២ (Last Name) <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" placeholder="ឧ. ចាន់" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">តួនាទី / ផ្នែក (Role) <span class="text-danger">*</span></label>
                            <select name="role" class="form-control" required>
                                <option value="doctor">វេជ្ជបណ្ឌិត (Doctor)</option>
                                <option value="nurse">គិលានុបដ្ឋាក (Nurse)</option>
                                <option value="pharmacist">ឱសថការី (Pharmacist)</option>
                                <option value="lab_technician">បច្ចេកទេសមន្ទីរពិសោធន៍ (Lab Tech)</option>
                                <option value="admin">អ្នកគ្រប់គ្រង (Admin)</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ជំនាញឯកទេស (Specialization)</label>
                            <input type="text" name="specialization" class="form-control" placeholder="ឧ. ជំងឺទូទៅ, ជំងឺបេះដូង...">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">លេខទូរស័ព្ទ (Phone Number)</label>
                            <input type="text" name="phone" class="form-control" placeholder="ឧ. 012 345 678">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ស្ថានភាព (Status) <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active">សកម្ម (Active)</option>
                                <option value="inactive">អសកម្ម (Inactive)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Modal Edit Employee ====== --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-edit">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-edit mr-2"></i> កែប្រែព័ត៌មានបុគ្គលិក (Edit Employee)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editEmployeeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">កូដបុគ្គលិក (Employee Code) <span class="text-danger">*</span></label>
                            <input type="text" id="edit_employee_code" name="employee_code" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ដេប៉ាតឺម៉ង់ (Department) <span class="text-danger">*</span></label>
                            <select id="edit_department_id" name="department_id" class="form-control" required>
                                <option value="">-- ជ្រើសរើសដេប៉ាតឺម៉ង់ --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">នាមត្រកូល / ឈ្មោះទី១ (First Name) <span class="text-danger">*</span></label>
                            <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ឈ្មោះខ្លួន / ឈ្មោះទី២ (Last Name) <span class="text-danger">*</span></label>
                            <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">តួនាទី / ផ្នែក (Role) <span class="text-danger">*</span></label>
                            <select id="edit_role" name="role" class="form-control" required>
                                <option value="doctor">វេជ្ជបណ្ឌិត (Doctor)</option>
                                <option value="nurse">គិលានុបដ្ឋាក (Nurse)</option>
                                <option value="pharmacist">ឱសថការី (Pharmacist)</option>
                                <option value="lab_technician">បច្ចេកទេសមន្ទីរពិសោធន៍ (Lab Tech)</option>
                                <option value="admin">អ្នកគ្រប់គ្រង (Admin)</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ជំនាញឯកទេស (Specialization)</label>
                            <input type="text" id="edit_specialization" name="specialization" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">លេខទូរស័ព្ទ (Phone Number)</label>
                            <input type="text" id="edit_phone" name="phone" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">ស្ថានភាព (Status) <span class="text-danger">*</span></label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="active">សកម្ម (Active)</option>
                                <option value="inactive">អសកម្ម (Inactive)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-sync-alt mr-1"></i> កែប្រែទិន្នន័យ</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====== Modal Delete Employee ====== --}}
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle mr-2"></i> បញ្ជាក់ការលុបបុគ្គលិក</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteEmployeeForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center p-4">
                    <p class="mb-2 fs-5">តើអ្នកពិតជាចង់លុបបុគ្គលិកឈ្មោះ <strong id="deleteEmployeeName" class="text-danger"></strong> នេះមែនទេ?</p>
                    <small class="text-muted">សកម្មភាពនេះមិនអាចត្រឡប់ក្រោយវិញបានឡើយ។</small>
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-danger px-4"><i class="fas fa-trash mr-1"></i> លុបចេញ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
$(document).ready(function () {
    let debounceTimer;

    function currentParams(page = 1) {
        return {
            page: page,
            search: $('#search').val(),
            role: $('#filterRole').val(),
            department_id: $('#filterDepartment').val(),
            status: $('#filterStatus').val()
        };
    }

    function loadEmployees(page = 1) {
        const params = currentParams(page);
        $('#employeeTableContainer').css('opacity', '0.5');

        $.ajax({
            url: "{{ route('employee.index') }}",
            method: 'GET',
            data: params,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                $('#employeeTableContainer').html(res.html).css('opacity', '1');
                if (res.total !== undefined) $('#statTotalEmployees').text(res.total);
                if (res.doctors !== undefined) $('#statTotalDoctors').text(res.doctors);
                if (res.nurses !== undefined) $('#statTotalNurses').text(res.nurses);
                if (res.active !== undefined) $('#statActiveEmployees').text(res.active);

                const qs = $.param(params);
                history.replaceState(null, '', "{{ route('employee.index') }}?" + qs);
            },
            error: function () {
                $('#employeeTableContainer').css('opacity', '1');
                console.error('Failed to load employee list.');
            }
        });
    }

    // Debounced search
    $('#search').on('keyup', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            loadEmployees(1);
        }, 400);
    });

    // Dropdown filters
    $('#filterRole, #filterDepartment, #filterStatus').on('change', function () {
        loadEmployees(1);
    });

    // AJAX Pagination
    $(document).on('click', '#employeeTableContainer .pagination a', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
        loadEmployees(page);
    });

    // Edit modal fetch & populate
    $(document).on('click', '.btn-edit', function () {
        let id = $(this).data('id');
        $.get("{{ url('employee/edit') }}/" + id, function (data) {
            $('#edit_employee_code').val(data.employee_code);
            $('#edit_department_id').val(data.department_id);
            $('#edit_first_name').val(data.first_name);
            $('#edit_last_name').val(data.last_name);
            $('#edit_role').val(data.role);
            $('#edit_specialization').val(data.specialization);
            $('#edit_phone').val(data.phone);
            $('#edit_status').val(data.status);

            $('#editEmployeeForm').attr('action', "{{ url('employee/update') }}/" + id);
        }).fail(function() {
            showToast('មិនអាចទាញយកទិន្នន័យបុគ្គលិកបានទេ', 'error');
        });
    });

    // Delete modal assign
    $(document).on('click', '.btn-delete', function () {
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#deleteEmployeeName').text(name);
        $('#deleteEmployeeForm').attr('action', "{{ url('employee/delete') }}/" + id);
    });

    // Toast helper
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
