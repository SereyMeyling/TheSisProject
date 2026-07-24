@extends('adminlte::page')

@section('title', 'Backup & Restore')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <h2 class="page-title">
            <i class="fas fa-database"></i>
            ការបម្រុងទុកទិន្នន័យ
        </h2>
    </div>


    <div class="card shadow-sm">

        <div class="card-header p-0">

            <ul class="nav nav-tabs" id="backupTab" role="tablist">

                <li class="nav-item">
                    <a class="nav-link active" id="backup-tab" data-toggle="tab" href="#backup" role="tab">

                        <i class="fas fa-download"></i>
                        Backup
                    </a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" id="restore-tab" data-toggle="tab" href="#restore" role="tab">

                        <i class="fas fa-upload"></i>
                        Restore
                    </a>
                </li>

            </ul>

        </div>


        <div class="card-body">


            <div class="tab-content">


                {{-- BACKUP TAB --}}
                <div class="tab-pane fade show active" id="backup" role="tabpanel">
                    <div class="alert alert-success">
                        <i class=" fas fa-info-circle"></i>
                        Backup database ដើម្បីរក្សាទុកទិន្នន័យសុវត្ថិភាព។
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">

                                <tr>

                                    <th width="30%">
                                        ឈ្មោះ File Backup
                                    </th>

                                    <th>
                                        ថ្ងៃបង្កើត
                                    </th>

                                    <th width="180px">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <tr>

                                    <td>
                                        database_backup.sql
                                    </td>


                                    <td>
                                        22-07-2026 09:00 AM
                                    </td>


                                    <td>

                                        <a href="#" class="btn btn-primary btn-sm">

                                            <i class="fas fa-download"></i>
                                            Download

                                        </a>



                                        <button class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete backup?')">

                                            <i class="fas fa-trash"></i>

                                        </button>


                                    </td>

                                </tr>


                            </tbody>


                        </table>


                    </div>
                    <button class="btn btn-primary">
                        <i class="fas fa-database"></i>
                        Create Backup Now
                    </button>
                </div>

                {{-- RESTORE TAB --}}
                <div class="tab-pane fade" id="restore" role="tabpanel">

                    <div class="alert alert-success">
                        <i class="fas fa-exclamation-triangle"></i>
                        Restore database នឹងជំនួសទិន្នន័យបច្ចុប្បន្ន។

                    </div>



                    <form enctype="multipart/form-data">


                        <div class="form-group">

                            <label>
                                Upload Backup File
                            </label>


                            <div class="custom-file">

                                <input type="file" class="custom-file-input" id="backupFile">

                                <label class="custom-file-label" for="backupFile">

                                    Choose file

                                </label>

                            </div>

                        </div>



                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-upload"></i>

                            Restore Database

                        </button>


                    </form>



                </div>



            </div>


        </div>

    </div>


</div>


@stop



@section('css')

<style>
    :root {
        --primary-color: #006D36;

    }

    .page-title {
        font-weight: 700;

    }

    .alert-success {
        background-color: var(--primary-color) !important;
        color: #fff;
    }

    .card {

        border-radius: 15px;
    }

    .nav-tabs .nav-link {
        font-weight: 600;
        padding: 15px 25px;
    }

    .nav-tabs .nav-link.active {
        color: #006D36;
        border-top: 3px solid #006D36;
    }

    .table th {
        background: #f8f9fa;
    }


    .btn {
        border-radius: 8px;

    }


    .custom-file-label {

        border-radius: 10px;

    }
</style>


@stop



@section('js')

<script>

    $(document).ready(function () {

        $('.custom-file-input').on('change', function () {

            let fileName = $(this).val().split('\\').pop();

            $(this)
                .next('.custom-file-label')
                .html(fileName);

        });


    });


</script>


@stop