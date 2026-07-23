@extends('adminlte::page')

@section('title', 'User Management')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 ">
    <h2 class="page-title mb-0 mt-3">ការគ្រប់គ្រងអ្នកប្រើប្រាស់</h2>
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

        <div class="toolbar flex-wrap">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" class="form-control border-0"
                    placeholder="ស្វែងរកអ្នកប្រើប្រាស់ (ឈ្មោះ, អ៊ីមែល, Username)">
            </div>
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

        // ---- Reset 2FA: fill confirmation modal + point form at the right user ----
        $(document).on('click', '.btn-reset-2fa', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#reset2faUserName').text(name);
            $('#reset2faForm').attr('action', "{{ url('user') }}/" + id + "/reset-2fa");
        });
    });
</script>
@stop
