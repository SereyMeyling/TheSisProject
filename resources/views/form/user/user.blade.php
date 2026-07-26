@extends('adminlte::page')

@section('title', 'User Management')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0 mt-3">ការគ្រប់គ្រងអ្នកប្រើប្រាស់</h2>
    <button type="button" class="btn btn-success mt-3" data-toggle="modal" data-target="#modalCreateUser">
        <i class="fas fa-user-plus mr-1"></i> បង្កើតអ្នកប្រើប្រាស់ថ្មី
    </button>
</div>
<div id="userSuccessToast" class="alert alert-success alert-dismissible fade show d-none mb-3" role="alert">
    <i class="fas fa-check-circle mr-2"></i><span id="userSuccessToastMessage"></span>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-light-success">
                <i class="fas fa-users"></i>
            </div>

            <div>
                <small>អ្នកប្រើប្រាស់សរុប</small>

                <h3 id="totalUser">{{ $totalUser }}</h3>
            </div>
        </div>
    </div>

</div>


<div class="card">
    <div class="card-body p-0">

        <div class="toolbar flex-wrap justify-content-between">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" class="form-control border-0"
                    placeholder="ស្វែងរកអ្នកប្រើប្រាស់ (ឈ្មោះ, អ៊ីមែល, Username)">
            </div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalCreateUser">
                <i class="fas fa-user-plus mr-1"></i> បង្កើតអ្នកប្រើប្រាស់ថ្មី
            </button>
        </div>
    </div>

    <div class="container">

        <div id="userTableContainer">
            @include('form.user.partials.table')
        </div>
    </div>

</div>

{{-- ── RESET 2FA CONFIRMATION MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalReset2FA" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-shield-alt mr-1"></i>កំណត់ 2FA ឡើងវិញ</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" id="reset2faForm">
                @csrf
                <div class="modal-body text-center">
                    <p class="mb-1">តើអ្នកពិតជាចង់កំណត់ Two-Factor Authentication របស់</p>
                    <strong id="reset2faUserName" class="text-warning"></strong>
                    <p class="mb-0">ឡើងវិញមែនទេ?</p>
                    <p class="mb-0 text-muted small mt-2">អ្នកប្រើប្រាស់នេះនឹងត្រូវបានស្នើឱ្យបង្កើត Google Authenticator ថ្មីនៅពេលចូលប្រើលើកក្រោយ។</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-shield-alt mr-1"></i><span>កំណត់ឡើងវិញ</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── UPDATE ROLE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalUpdateRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-mobile-fit">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-user-tag mr-1"></i>កំណត់តួនាទីអ្នកប្រើប្រាស់</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" id="updateRoleForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="mb-2">កំណត់តួនាទីសម្រាប់ <strong id="updateRoleUserName" class="text-primary"></strong></p>
                    <div class="form-group">
                        <label for="selectUserRole" class="small text-muted">ជ្រើសរើសតួនាទី (Role)</label>
                        <select name="role" id="selectUserRole" class="form-control" required>
                            <option value="">-- ជ្រើសរើសតួនាទី --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── CREATE NEW USER MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalCreateUser" tabindex="-1" aria-labelledby="modalCreateUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header bg-success text-white" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title font-weight-bold" id="modalCreateUserLabel">
                    <i class="fas fa-user-plus mr-2"></i>បង្កើតអ្នកប្រើប្រាស់ថ្មី (Create New User)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createUserForm" action="{{ route('user.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div id="createUserGeneralAlert" class="alert alert-danger d-none mb-3"></div>

                    <!-- Full Name -->
                    <div class="form-group mb-3">
                        <label for="create_name" class="font-weight-bold mb-1">ឈ្មោះ (Full Name) <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="create_name" class="form-control" placeholder="បញ្ចូលឈ្មោះពេញ" required>
                        <div class="invalid-feedback" id="error_create_name"></div>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group mb-3">
                        <label for="create_email" class="font-weight-bold mb-1">អ៊ីមែល (Email Address) <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="create_email" class="form-control" placeholder="example@hospital.com" required>
                        <div class="invalid-feedback" id="error_create_email"></div>
                    </div>

                    <!-- Username -->
                    <div class="form-group mb-3">
                        <label for="create_username" class="font-weight-bold mb-1">ឈ្មោះអ្នកប្រើប្រាស់ (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="create_username" class="form-control" placeholder="បញ្ចូល Username" required>
                        <div class="invalid-feedback" id="error_create_username"></div>
                    </div>

                    <!-- Password -->
                    <div class="form-group mb-3">
                        <label for="create_password" class="font-weight-bold mb-1">ពាក្យសម្ងាត់ (Password) <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="create_password" class="form-control" placeholder="យ៉ាងហោចណាស់ ៨ តួអក្សរ" required minlength="8">
                        <div class="invalid-feedback" id="error_create_password"></div>
                    </div>

                    <!-- Role Select Dropdown -->
                    <div class="form-group mb-3">
                        <label for="create_role" class="font-weight-bold mb-1">តួនាទី (Role) <span class="text-danger">*</span></label>
                        <select name="role" id="create_role" class="form-control custom-select" required>
                            <option value="">-- ជ្រើសរើសតួនាទី (Select Role) --</option>
                            <option value="admin">Admin</option>
                            <option value="doctor">Doctor</option>
                            <option value="cashier">Cashier</option>
                            <option value="nurse">Nurse</option>
                            <option value="pharmacist">Pharmacist</option>
                        </select>
                        <div class="invalid-feedback" id="error_create_role"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between bg-light">
                    <button type="button" class="btn btn-light border px-4" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitCreateUser">
                        <i class="fas fa-save mr-1"></i> រក្សាទុក (Create User)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .page-title {
        font-weight: 700;
        color: #222;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 5px 18px rgba(0, 0, 0, .05);

    }

    .stat-card h3 {
        margin: 0;
        font-weight: 700;
    }

    .icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
    }

    .bg-light-success {
        background: #dff6e8;
        color: #18864b;
    }

    /* Card */
    .card {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .05);
    }

    /* Toolbar */
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        gap: 12px;
    }

    .search-box {
        display: flex;
        align-items: center;
        background: #f3f5f7;
        border-radius: 12px;
        padding: 0 15px;
        width: 320px;

    }

    .search-box input {
        background: none;
        box-shadow: none;
    }

    .search-box i {
        color: #888;
    }

    /* Table */
    .table thead th {
        background: #f5f6f7;
        border: none;
        color: #666;
        font-size: 14px;
    }

    .table td {
        vertical-align: middle;
        border-top: 1px solid #eee;
    }

    /* Actions */
    .action-icons {
        display: flex;
        gap: 8px;
    }

    /* Pagination */
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 3px;
        color: #198754;
    }

    .pagination .active .page-link {
        background: #198754;
        border-color: #198754;

    }

    /* AJAX loading state for the table container */
    #userTableContainer {
        position: relative;
        min-height: 120px;
        transition: opacity .15s ease;
    }

    #userTableContainer.loading {
        opacity: .45;
        pointer-events: none;
    }

    #userTableContainer.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 34px;
        height: 34px;
        margin: -17px 0 0 -17px;
        border: 3px solid #ddd;
        border-top-color: #198754;
        border-radius: 50%;
        animation: user-spin .6s linear infinite;
    }

    @keyframes user-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

@stop

@section('js')
@parent
<script>
    $(document).ready(function () {

        const $container = $('#userTableContainer');
        const $search = $('#search');
        let debounceTimer;

        function currentParams(page) {
            return {
                search: $search.val(),
                page: page || 1,
            };
        }

        function loadUsers(page) {
            const params = currentParams(page);

            $container.addClass('loading');

            $.ajax({
                url: "{{ route('user.index') }}",
                method: 'GET',
                data: params,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    $container.html(res.html);
                    $('#totalUser').text(res.total);

                    const qs = $.param(params);
                    history.replaceState(null, '', "{{ route('user.index') }}?" + qs);
                },
                error: function () {
                    console.error('Failed to load user list.');
                },
                complete: function () {
                    $container.removeClass('loading');
                }
            });
        }

        // ---- search ----
        $search.on('keyup', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                loadUsers(1);
            }, 400);
        });

        $(document).on('click', '#userTableContainer .pagination a', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            const page = new URL(href, window.location.origin).searchParams.get('page') || 1;
            loadUsers(page);
        });

        // ---- Edit Role: fill modal + set action URL ----
        $(document).on('click', '.btn-edit-role', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let role = $(this).data('role');
            $('#updateRoleUserName').text(name);
            $('#selectUserRole').val(role);
            $('#updateRoleForm').attr('action', "{{ url('user') }}/" + id + "/role");
        });

        // ---- Reset 2FA: fill confirmation modal + point form at the right user ----
        $(document).on('click', '.btn-reset-2fa', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#reset2faUserName').text(name);
            $('#reset2faForm').attr('action', "{{ url('user') }}/" + id + "/reset-2fa");
        });

        // ---- AJAX Create User Submission ----
        $('#createUserForm').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $('#btnSubmitCreateUser');
            const $alert = $('#createUserGeneralAlert');

            // Reset field validations & error alert
            $form.find('.form-control, .custom-select').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').text('');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងរក្សាទុក...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> រក្សាទុក (Create User)');
                    $('#modalCreateUser').modal('hide');
                    $form[0].reset();

                    // Refresh table & show success notification
                    loadUsers(1);

                    $('#userSuccessToastMessage').text(res.message || 'User created successfully');
                    $('#userSuccessToast').removeClass('d-none');
                    setTimeout(function () {
                        $('#userSuccessToast').addClass('d-none');
                    }, 5000);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> រក្សាទុក (Create User)');

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function (field, messages) {
                            const $input = $('#create_' + field);
                            $input.addClass('is-invalid');
                            $('#error_create_' + field).text(messages[0]);
                        });
                    } else {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while creating the user.';
                        $alert.removeClass('d-none').text(msg);
                    }
                }
            });
        });
    });
</script>
@stop
