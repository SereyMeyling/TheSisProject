@extends('adminlte::page')

@section('title', 'General Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <h2 class="page-title mb-0">
        <i class="fas fa-sliders-h"></i> ការកំណត់ទូទៅ
    </h2>
    <button type="submit" form="generalSettingsForm" class="btn btn-success px-4">
        <i class="fas fa-save mr-2"></i>
        រក្សាទុក
    </button>
</div>

<div class="card setting-card">
    <div class="card-body">
        <form id="generalSettingsForm" action="{{ route('settingsgeneral.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
           
            <div class="row">

                <!-- Left -->
                <div class="col-lg-8">

                    <div class="form-group">
                        <label>ឈ្មោះប្រព័ន្ធ</label>
                        <input name="system_name" value="{{ old('system_name', $setting->system_name) }}" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>លេខទូរសព្ទ</label>
                                <input type="text" name="phone" value="{{ old('phone', $setting->phone) }}" class="form-control" placeholder="+855">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>អ៊ីមែល</label>
                                <input type="email" name="email" value="{{ old('email', $setting->email) }}" class="form-control" placeholder="@gmail.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>អាសយដ្ឋាន</label>
                        <textarea name="address" class="form-control" rows="3">{{ old('address', $setting->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="text" name="facebook" value="{{ old('facebook', $setting->facebook) }}" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Telegram</label>
                                <input type="text" name="telegram" value="{{ old('telegram', $setting->telegram) }}" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ម៉ោងធ្វើការ</label>
                        <input type="text" name="working_hours" value="{{ old('working_hours', $setting->working_hours) }}" class="form-control" placeholder="ចន្ទ-សុក្រ 8:00AM - 5:00PM">
                    </div>

                </div>

                <!-- Right -->
                <div class="col-lg-4">

                    <div class="image-box mb-4">
                        <label class="font-weight-bold">Logo</label>

                        <div class="preview">
                            <img id="logoPreview"
                                 src="{{ $setting->logo ? (str_starts_with($setting->logo, 'http') ? $setting->logo : asset('storage/' . $setting->logo)) : 'https://via.placeholder.com/150?text=Logo' }}"
                                 alt="Logo">
                        </div>

                        <input type="file" name="logo" accept="image/*" class="form-control mt-3" id="logoInput">
                    </div>

                    <div class="image-box">
                        <label class="font-weight-bold">Favicon</label>

                        <div class="preview">
                            <img id="faviconPreview"
                                 src="{{ $setting->favicon ? (str_starts_with($setting->favicon, 'http') ? $setting->favicon : asset('storage/' . $setting->favicon)) : 'https://via.placeholder.com/150?text=Favicon' }}"
                                 alt="Favicon">
                        </div>

                        <input type="file" name="favicon" accept="image/*" class="form-control mt-3" id="faviconInput">
                    </div>

                </div>

            </div>
        </form>
    </div>
</div>

@stop

@section('css')
<style>
    .page-title {
        font-weight: 700;
    }

    .setting-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
    }

    .setting-card .card-body {
        padding: 35px;
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

    textarea.form-control {
        height: auto;
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

    .btn-success {
        border-radius: 10px;
        font-weight: 600;
        padding: 10px 25px;
    }
</style>
@stop

@section('js')
@parent
<script>
    function previewImage(input, previewId) {
        input.addEventListener("change", function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById(previewId).src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    previewImage(document.getElementById("logoInput"), "logoPreview");
    previewImage(document.getElementById("faviconInput"), "faviconPreview");
</script>
@stop
