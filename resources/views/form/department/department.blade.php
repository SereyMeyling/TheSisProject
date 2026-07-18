@extends('adminlte::page')

@section('title', 'Department')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 ">
    <h2 class="page-title mb-0 mt-3">ការគ្រប់គ្រងដេប៉ាតឺម៉ង់</h2>
    <button class="btn btn-success btn-add mt-3" data-toggle="modal" data-target="#modalCreate">
        <i class="fas fa-plus-circle mr-1"></i>
        បន្ថែម
    </button>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-light-success">
                <i class="fas fa-hospital"></i>
            </div>

            <div>
                <small>ដេប៉ាតឺម៉ង់សរុប</small>
                <h3>{{ $totalDepartment }}</h3>
            </div>
        </div>
    </div>

</div>


<div class="card">
    <div class="card-body p-0">
        <div class="toolbar">
            <form method="GET" action="{{ route('department.index') }}" class="d-flex w-100 justify-content-between">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" name="search" class="form-control border-0"
                        placeholder="ស្វែងរកដេប៉ាតឺម៉ង់" value="{{ request('search') }}">
                </div>

                <div class="d-flex align-items-center">
                    {{-- date filter --}}
                    <input type="date" name="date" class="form-control mr-2" value="{{ request('date') }}">

                    <button type="submit" class="btn btn-light mr-2">
                        <i class="fas fa-search"></i>
                    </button>

                    @if(request('search') || request('date'))
                        <a href="{{ route('department.index') }}" class="btn btn-light">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
        <div class="container">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>លរ</th>
                        <th>ឈ្មោះ</th>
                        <th>ការពិពណ៏នា</th>
                        <th>ថ្ថ្ងៃបង្កើត</th>
                        <th>សកម្មភាព</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($department as $depart)
                        <tr>
                            <td>{{ $depart->department_id }}</td>
                            <td>{{ $depart->department_name }}</td>
                            <td>{{ $depart->description }}</td>
                            <td>{{ $depart->created_at->format('d M, Y') }}</td>
                            <td>
                                <div class="action-icons">
                                    {{-- Edit --}}
                                    <button class="btn btn-sm btn-outline-secondary btn-edit" data-toggle="modal"
                                        data-target="#modalEdit" data-id="{{ $depart->department_id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- Delete --}}
                                    <button class="btn btn-sm btn-outline-secondary btn-delete" data-toggle="modal"
                                        data-id="{{ $depart->department_id }}" data-name="{{ $depart->department_name }}"
                                        data-target="#modalDelete">

                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                {!! $department->links('pagination::bootstrap-4') !!}
            </div>

        </div>

    </div>

</div>

{{-- ====== Create Modal ====== --}}

<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">បន្ថែមដេប៉ាតឺម៉ង់</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('department.store') }}">
                @csrf
                <input type="hidden" name="_modal" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>ឈ្មោះ</label>
                            <input type="text" name="department_name" class="form-control" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>ការពិពណ៏នា</label>
                            <input type="text" name="description" class="form-control" required>
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
    <div class="modal-dialog modal-dialog-scrollable modal-mobile-fit">
        <div class="modal-content modal-purple">
            <div class="modal-header">
                <h5 class="modal-title">កែទិន្នន័យដេប៉ាតឺម៉ង់</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form method="POST" id="editForm">
                <input type="hidden" name="_modal" value="edit">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>ឈ្មោះ</label>
                            <input type="text" id="department_name" name="department_name" class="form-control"
                                required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>ការពិពណ៏នា</label>
                            <input type="text" id="description" name="description" class="form-control" required>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">បោះបង់</button>
                    <button type="submit" class="btn btn-primary">រក្សាទុក</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ── DELETE MODAL ─────────────────────────────────────────────── --}}
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-mobile-fit">
        <div class="modal-content modal-green">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle mr-1"></i>លុបទិន្នន័យ</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" id="deleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center">
                    <p class="mb-1">តើអ្នកពិតជាចង់លុប</p>
                    <strong id="deleteDepartmentName" class="text-danger"></strong>
                    <p class="mb-0">មែនទេ?</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">មិនលុបទេ</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i
                            class="fas fa-trash mr-1"></i><span>លុប</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    /* Heading */
    .page-title {
        font-weight: 700;
        color: #222;
    }

    /* Add Button */
    .btn-add {

        border-radius: 10px;
        padding: 8px 20px;
        font-weight: 600;

    }

    /* Statistics */
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

    .bg-light-danger {
        background: #ffe4ec;
        color: #d63384;
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

    /* Department */
    .department-name {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dept-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eef7f1;
        color: #18864b;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Actions */
    .action-icons i {
        font-size: 15px;
        transition: .25s;
    }

    .action-icons a:hover {
        transform: scale(1.15);
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
</style>

@stop

@section('js')
<script>
    $(document).ready(function () {
        //edit
        $(document).on('click', '.btn-edit', function () {
            let id = $(this).data('id');
            $.get("{{ url('department/edit') }}/" + id, function (data) {

                $('#department_name').val(data.department_name);
                $('#description').val(data.description);

                $('#editForm').attr(
                    'action',
                    "{{ url('department/update') }}/" + id
                );

            });
        });
        //delete
        $(document).on('click', '.btn-delete', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#deleteDepartmentName').text(name);
            $('#deleteForm').attr('action', "{{ url('department/delete') }}/" + id);
        });
        //search
        $(document).ready(function () {
            let timer;
            $('#search').on('keyup', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    $(this).closest('form').submit();
                }, 500);
            });
        });
    });
</script>
@stop
