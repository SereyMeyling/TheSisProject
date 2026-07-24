@extends('adminlte::page')

@section('title', 'General Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title mb-0">
       <i class="fas fa-qrcode"></i> ការកំណត់ QR CODE
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
                    <label>ប្រភេទធនាគារ</label>
                    <select name="" id="" class="form-control">
                        <option value="">ABA BANK</option>
                        <option value="">ACLEDA</option>
                        <option value="">WING</option>
                        <option value="">TRUE MONEY</option>
                        <option value="">BAKONG</option>
                    </select>
                </div>

            </div>
            <div class="col-6">
                <div class="form-group">
                    <label>ឈ្មោះគណនី</label>
                    <input type="text" class="form-control" placeholder="">
                </div>

                <div class="form-group">
                    <label>លេខគណនី</label>
                    <input type="text" class="form-control" placeholder="">
                </div>
            </div>
            <div class="col-6">
                <div class="image-box mb-4">

                    <label class="font-weight-bold">រូប </label>

                    <div class="preview">
                        <img src="https://placehold.co/140x140?text=Logo" id="logoPreview">
                    </div>

                    <input type="file" class="form-control mt-3" id="logoInput">

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

    .image-box {
        border: 1px solid #eee;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        background: #fafafa;
    }

    .preview {
        width: 150px;
        height: 150px;
        margin: auto;
        border-radius: 12px;
        border: 2px dashed #dcdcdc;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        background: white;

    }

    .preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;

    }
</style>

@stop

@section('js')
@parent

<script>

</script>

@stop
