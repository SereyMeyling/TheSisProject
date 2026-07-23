@extends('adminlte::page')

@section('title', 'Pharmacy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 mt-3">

    <h2 class="page-title">
        <i class="fas fa-pills"></i>
        ឱសថស្ថាន
    </h2>


    <button class="btn btn-success btn-add" data-toggle="modal" data-target="#modalCreate">

        <i class="fas fa-plus-circle"></i>
        បន្ថែមថ្នាំ

    </button>

</div>


{{-- Dashboard Cards --}}

<div class="row mb-4">


    <div class="col-lg-3 col-md-6 mb-3">

        <div class="stat-card">

            <div class="icon bg-success-light">

                <i class="fas fa-pills"></i>

            </div>

            <div>

                <small>ថ្នាំសរុប</small>

                <h3 id="totalMedicine">
                    0
                </h3>

            </div>

        </div>

    </div>



    <div class="col-lg-3 col-md-6 mb-3">

        <div class="stat-card">

            <div class="icon bg-blue-light">

                <i class="fas fa-boxes"></i>

            </div>


            <div>

                <small>តម្លៃស្តុក</small>

                <h3>
                    $0
                </h3>

            </div>

        </div>

    </div>



    <div class="col-lg-3 col-md-6 mb-3">


        <div class="stat-card">

            <div class="icon bg-warning-light">

                <i class="fas fa-exclamation-triangle"></i>

            </div>


            <div>

                <small>ស្តុកជិតអស់</small>

                <h3>
                    0
                </h3>

            </div>


        </div>


    </div>




    <div class="col-lg-3 col-md-6 mb-3">


        <div class="stat-card">


            <div class="icon bg-danger-light">

                <i class="fas fa-calendar-times"></i>

            </div>


            <div>

                <small>ជិតផុតកំណត់</small>

                <h3>
                    0
                </h3>

            </div>


        </div>


    </div>


    <div class="mt-3 card setting-card">
        <div class="card-body">
            <div class="row">

                <!-- Left -->
                <div class="col-lg-8">

                    <div class="container">

                        <div id="departmentTableContainer">
                            @include('form.phamacy.table')
                        </div>
                    </div>


                </div>

                <!-- Right -->
                <div class="col-lg-4">

                    <div class="mb-4">
                        <div class="card-header">
                            <label class="font-weight-bold">សកម្មភាពថ្មីៗ</label>

                        </div>
                        <div class="card-body">
                            <div class="preview">
                                <icon></icon>
                                <h3>Batch Arrival : Ibuprofen 400mg</h3>
                                <p>supplier pharmacorp +2000 units</p>
                                <p><span>14:20</span></p>
                            </div>

                        </div>
                        <div class="card-footer">
                            <a href="">មើលសកម្មភាពទាំងអស់</a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>


    {{-- ====== Create Modal ====== --}}

    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-mobile-fit">
            <div class="modal-content modal-purple">
                <div class="modal-header">
                    <h5 class="modal-title">បន្ថែមថ្មី</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    @csrf
                    <input type="hidden" name="_modal" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>ឈ្មោះថ្នាំ</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label>លេខសម្គាល់</label>
                                <input type="text" name="description" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>ប្រភេទ</label>
                                <select class="form-control" required>
                                    <option value="">ជ្រើសរើសប្រភេទ</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>ទម្រង់ Dossage</label>
                                <select class="form-control" required>
                                    <option value="">ជ្រើសរើស Dossage</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>ចំនួន mg</label>
                                <input type="text" name="description" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>ថ្ងៃផុតកំណត់</label>
                                <input type="date" name="description" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>ឈ្មោះអ្នកផ្គត់ផ្គង់</label>
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
        .page-title {
            font-weight: 700;
        }


        .stat-card {

            background: white;
            border-radius: 15px;
            padding: 20px;

            display: flex;
            align-items: center;

            gap: 18px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, .06);

            height: 100%;

        }


        .stat-card h3 {

            margin: 0;

            font-weight: 700;

        }


        .stat-card small {

            color: #777;

        }


        .icon {

            width: 55px;
            height: 55px;

            border-radius: 15px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 22px;

        }


        .bg-success-light {

            background: #dff5e8;
            color: #006D36;

        }


        .bg-blue-light {

            background: #e4efff;
            color: #2563eb;

        }


        .bg-warning-light {

            background: #fff3cd;
            color: #d97706;

        }


        .bg-danger-light {

            background: #ffe4e6;
            color: #dc2626;

        }



        .activity-item {

            display: flex;

            gap: 15px;

            padding: 15px 0;

            border-bottom: 1px solid #eee;

        }


        .activity-item i {

            font-size: 20px;

        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, .05);
        }
    </style>

    @stop

    @section('js')
    @parent

    <script>



    </script>

    @stop
