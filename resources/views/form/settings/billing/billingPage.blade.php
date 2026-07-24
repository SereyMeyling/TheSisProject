@extends('adminlte::page')

@section('title', 'General Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title mb-0">
        <i class="fas fa-file-invoice-dollar"></i> ការកំណត់វិក្កយបត្រ
    </h2>
    <button class="btn btn-success px-4">
        <i class="fas fa-save mr-2"></i>
        រក្សាទុក
    </button>
</div>

<div class="card setting-card">
    <div class="card-body">
        <div class="row">

            <div class="col-6">

                <div class="form-group">
                    <label>រូបិយវត្ថុ</label>
                    <input type="text" class="form-control" placeholder="៛">
                </div>

                <div class="form-group">
                    <label>ពន្ធ%</label>
                    <input type="text" class="form-control" placeholder="%">
                </div>
            </div>
            <div class="col-6">

                <div class="form-group">
                    <label>វិក័យប័ត្រ Prefix</label>
                    <input type="text" class="form-control" placeholder="">
                </div>

                <div class="form-group">
                    <label>វិក័យប័ត្រ Footer</label>
                    <input type="text" class="form-control" placeholder="">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label>លេខវិក័យប័ត្រអូតូ</label>
                    <input type="text" class="form-control" placeholder="">
                </div>
                <div class="form-group">
                    <label>ព្រីនទំហំ</label>
                    <select name="" id="" class="form-control">
                        <option value="">A4</option>
                        <option value="">80mm</option>
                    </select>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

@stop

@section('css')

<style>
    .page-title {
        font-weight: 700;
    }

    .form-group {
        margin-bottom: 22px;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 10px;
        height: 45px;
    }
</style>

@stop

@section('js')
@parent

<script>

</script>

@stop
