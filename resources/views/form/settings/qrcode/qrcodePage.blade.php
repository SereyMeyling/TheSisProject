@extends('adminlte::page')

@section('title', 'QR Code Settings')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">
                <i class="fas fa-qrcode text-dark mr-2"></i>
                ការកំណត់ QR Code
            </h1>
            <small class="text-muted">
                កំណត់វិធីទទួលការទូទាត់សម្រាប់ Billing
            </small>
        </div>

        <button type="submit" form="qrSettingForm" class="btn btn-primary px-4 mt-2 mt-md-0">
            <i class="fas fa-save mr-2"></i>
            រក្សាទុក
        </button>
    </div>
@stop

@section('content')

    <form id="qrSettingForm" enctype="multipart/form-data">
        @csrf

        {{-- QR SETUP MODE --}}
        <div class="card border-0 shadow-sm settings-card mb-4">
            <div class="card-body p-4">

                <div class="section-title mb-3">
                    <div class="section-icon">
                        <i class="fas fa-sliders-h text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">វិធីកំណត់ QR</h5>
                        <small class="text-muted">
                            ជ្រើសរើសរបៀបដែលប្រព័ន្ធនឹងប្រើ QR
                        </small>
                    </div>
                </div>

                <div class="row">

                    {{-- MANUAL --}}
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label
                            class="mode-card w-100 mb-0
                        {{ optional($qrSetting)->mode !== 'bakong' ? 'active' : '' }}"
                            id="labelModeManual">

                            <input type="radio" name="mode" value="manual" class="d-none" autocomplete="off"
                                {{ optional($qrSetting)->mode !== 'bakong' ? 'checked' : '' }}>

                            <div class="mode-icon">
                                <i class="fas fa-image"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="font-weight-bold">
                                    QR ដោយផ្ទាល់
                                </div>
                                <small class="text-muted">
                                    Upload QR ដែលមានស្រាប់ពីធនាគារ
                                </small>
                            </div>

                            <i class="fas fa-check-circle mode-check"></i>
                        </label>
                    </div>

                    {{-- BAKONG --}}
                    <div class="col-md-6">
                        <label
                            class="mode-card w-100 mb-0
                        {{ optional($qrSetting)->mode === 'bakong' ? 'active' : '' }}"
                            id="labelModeBakong">

                            <input type="radio" name="mode" value="bakong" class="d-none" autocomplete="off"
                                {{ optional($qrSetting)->mode === 'bakong' ? 'checked' : '' }}>

                            <div class="mode-icon">
                                <i class="fas fa-bolt"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="font-weight-bold">
                                    Bakong API
                                </div>
                                <small class="text-muted">
                                    បង្កើត Dynamic KHQR តាម Invoice
                                </small>
                            </div>

                            <i class="fas fa-check-circle mode-check"></i>
                        </label>
                    </div>

                </div>

                <div class="info-alert mt-3">
                    <i class="fas fa-info-circle mr-2"></i>

                    <span id="manualInfo">
                        <strong>Manual:</strong>
                        ប្រើ QR ថេរដែលបានពីធនាគារ។
                        មិនអាចផ្ទៀងផ្ទាត់ការទូទាត់ដោយស្វ័យប្រវត្តិបានទេ។
                    </span>

                    <span id="bakongInfo" class="d-none">
                        <strong>Bakong API:</strong>
                        ត្រូវការគណនី Bakong និង API Token
                        ដើម្បីបង្កើត Dynamic KHQR។
                    </span>
                </div>

            </div>
        </div>


        {{-- MANUAL QR --}}
        <div id="manualModeSection" class="{{ optional($qrSetting)->mode === 'bakong' ? 'd-none' : '' }}">

            <div class="card border-0 shadow-sm settings-card mb-4">
                <div class="card-body p-4">

                    <div class="section-title mb-4">
                        <div class="section-icon bg-success-light text-success">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">QR Code ដោយផ្ទាល់</h5>
                            <small class="text-muted">
                                បញ្ចូល QR Code ដែលបានពីធនាគារ
                            </small>
                        </div>
                    </div>

                    <div class="row align-items-center">

                        <div class="col-lg-5 mb-4 mb-lg-0">
                            <div class="qr-upload-box">

                                <div class="qr-preview">
                                    <img src="{{ optional($qrSetting)->manual_qr_image
                                        ? Storage::url($qrSetting->manual_qr_image)
                                        : 'https://placehold.co/220x220?text=QR+Code' }}"
                                        id="manualQrPreview" alt="QR Code">
                                </div>

                                <label for="manualQrInput" class="btn btn-outline-success mt-3 mb-2">
                                    <i class="fas fa-upload mr-2"></i>
                                    ជ្រើសរើស QR
                                </label>

                                <input type="file" name="manual_qr_image" id="manualQrInput" class="d-none"
                                    accept="image/png,image/jpeg">

                                <small class="d-block text-muted">
                                    PNG / JPG · អតិបរមា 2MB
                                </small>

                            </div>
                        </div>

                        <div class="col-lg-7">

                            <div class="form-group">
                                <label>
                                    ឈ្មោះគណនី
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="account_name" class="form-control"
                                    value="{{ optional($qrSetting)->account_name }}" placeholder="ឧ. ABC Hospital">
                            </div>

                            <div class="form-group">
                                <label for="bank_name">ធនាគារ</label>
                                <span class="text-danger">*</span>

                                <select name="bank_name" id="bank_name" class="form-control" required>
                                    <option value="">-- ជ្រើសរើសធនាគារ --</option>

                                    @foreach (['ABA BANK', 'ACLEDA', 'WING', 'TRUE MONEY', 'BAKONG'] as $bank)
                                        <option value="{{ $bank }}"
                                            {{ optional($qrSetting)->bank_name == $bank ? 'selected' : '' }}>
                                            {{ $bank }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-0">
                                <label>លេខគណនី</label>
                                <span class="text-danger">*</span>

                                <input type="text" name="account_number" class="form-control" required
                                    value="{{ optional($qrSetting)->account_number }}"
                                    placeholder="សម្រាប់បង្ហាញតែប៉ុណ្ណោះ">
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>


        {{-- BAKONG --}}
        <div id="bakongModeSection" class="{{ optional($qrSetting)->mode === 'bakong' ? '' : 'd-none' }}">

            <div class="card border-0 shadow-sm settings-card mb-4">
                <div class="card-body p-4">

                    <div class="section-title mb-4">
                        <div class="section-icon bg-warning-light text-warning">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Bakong API</h5>
                            <small class="text-muted">
                                ព័ត៌មានសម្រាប់ Dynamic KHQR
                            </small>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="form-group">
                                <label>ប្រភេទគណនី</label>

                                <select name="account_type" class="form-control">
                                    <option value="individual"
                                        {{ optional($qrSetting)->account_type == 'individual' ? 'selected' : '' }}>
                                        បុគ្គល (Individual)
                                    </option>

                                    <option value="merchant"
                                        {{ optional($qrSetting)->account_type == 'merchant' ? 'selected' : '' }}>
                                        ហាង / ក្រុមហ៊ុន (Merchant)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>ធនាគារ</label>
                                <select name="bank_name" class="form-control">
                                    @foreach (['ABA BANK', 'ACLEDA', 'WING', 'TRUE MONEY', 'BAKONG'] as $bank)
                                        <option value="{{ $bank }}"
                                            {{ optional($qrSetting)->bank_name == $bank ? 'selected' : '' }}>
                                            {{ $bank }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    Bakong Account ID
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="bakong_account_id" class="form-control"
                                    value="{{ optional($qrSetting)->bakong_account_id }}"
                                    placeholder="ឧ. yourclinic@aclb">

                                <small class="text-muted">
                                    ឧ. yourclinic@aclb
                                </small>
                            </div>

                            <div class="form-group" id="merchantIdGroup">
                                <label>Merchant ID</label>

                                <input type="text" name="merchant_id" class="form-control"
                                    value="{{ optional($qrSetting)->merchant_id }}" placeholder="Merchant ID">
                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="form-group">
                                <label>ឈ្មោះគណនី</label>

                                <input type="text" name="account_name" class="form-control"
                                    value="{{ optional($qrSetting)->account_name }}" placeholder="ឈ្មោះមន្ទីរពេទ្យ">
                            </div>

                            <div class="form-group">
                                <label>លេខគណនី</label>

                                <input type="text" name="account_number" class="form-control"
                                    value="{{ optional($qrSetting)->account_number }}">
                            </div>

                            <div class="form-group">
                                <label>ទីក្រុង</label>

                                <input type="text" name="merchant_city" class="form-control"
                                    value="{{ optional($qrSetting)->merchant_city ?? 'Phnom Penh' }}">
                            </div>

                            <div class="form-group">
                                <label>លេខទូរស័ព្ទ</label>

                                <input type="text" name="mobile_number" class="form-control"
                                    value="{{ optional($qrSetting)->mobile_number }}" placeholder="012345678">
                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>

    </form>

@stop


@section('css')
    @parent

    <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .page-title {
        font-weight: 700;
        }
        .settings-card {
            border-radius: 16px;
            overflow: hidden;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eaf2ff;
            color: #0d6efd;
            flex-shrink: 0;
        }

        .bg-success-light {
            background: #eaf8ef;
        }

        .bg-warning-light {
            background: #fff7df;
        }

        .bg-info-light {
            background: #e8f7fb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            min-height: 44px;
            border-radius: 10px;
        }

        .mode-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border: 1px solid #dee2e6;
            border-radius: 14px;
            cursor: pointer;
            background: #fff;
            transition: all .2s ease;
        }

        .mode-card:hover {
            border-color: #17b466;
            background: #f8fbff;
        }

        .mode-card.active {
            border: 2px solid #006D36;
            background: #f5f9ff;
        }

        .mode-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eaf2ff;
            color: #006D36;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .mode-check {
            color: #006D36;
            display: none;
            font-size: 20px;
        }

        .mode-card.active .mode-check {
            display: block;
        }

        .info-alert {
            padding: 12px 15px;
            border-radius: 10px;
            background: #f4f7fb;
            color: #59636e;
            font-size: 14px;
        }

        .qr-upload-box {
            text-align: center;
            padding: 25px;
            border: 1px dashed #ced4da;
            border-radius: 15px;
            background: #fafbfc;
        }

        .qr-preview {
            width: 220px;
            height: 220px;
            margin: auto;
            padding: 10px;
            background: #fff;
            border: 1px solid #e2e6ea;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .logo-preview {
            width: 130px;
            height: 130px;
            margin: auto;
            padding: 8px;
            border: 1px solid #e1e5e9;
            border-radius: 14px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        @media (max-width: 767px) {
            .mode-card {
                padding: 14px;
            }

            .qr-preview {
                width: 190px;
                height: 190px;
            }

            .card-body {
                padding: 20px !important;
            }
        }
    </style>
@stop


@section('js')
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(function() {

            // Toastr Options
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 3000
            };

            function toggleMode() {
                const mode = $('input[name="mode"]:checked').val();
                const isBakong = mode === 'bakong';

                $('#bakongModeSection').toggleClass('d-none', !isBakong);
                $('#manualModeSection').toggleClass('d-none', isBakong);

                $('#manualInfo').toggleClass('d-none', isBakong);
                $('#bakongInfo').toggleClass('d-none', !isBakong);

                $('#labelModeManual').toggleClass('active', !isBakong);
                $('#labelModeBakong').toggleClass('active', isBakong);

                $('#manualModeSection').find('input, select').prop('disabled', isBakong);
                $('#bakongModeSection').find('input, select').prop('disabled', !isBakong);
            }

            toggleMode();

            $('input[name="mode"]').on('change', toggleMode);


            function toggleMerchantId() {
                const type = $('select[name="account_type"]').val();

                $('#merchantIdGroup').toggle(type === 'merchant');
            }

            toggleMerchantId();

            $('select[name="account_type"]').on('change', toggleMerchantId);

            // Manual QR preview
            $('#manualQrInput').on('change', function(e) {

                const file = e.target.files[0];

                if (file) {
                    $('#manualQrPreview').attr(
                        'src',
                        URL.createObjectURL(file)
                    );
                }
            });


            // Submit
            $('#qrSettingForm').on('submit', function(e) {

                e.preventDefault();

                const formData = new FormData(this);

                $.ajax({
                    url: "{{ route('settingsqrcode.update') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,

                    beforeSend: function() {
                        $('button[type="submit"][form="qrSettingForm"]')
                            .prop('disabled', true)
                            .html(
                                '<i class="fas fa-spinner fa-spin mr-2"></i> កំពុងរក្សាទុក...');
                    },

                    success: function(res) {
                        toastr.success(
                            res.message || 'ការកំណត់ QR Code ត្រូវបានរក្សាទុកដោយជោគជ័យ',
                        );

                        if (res.manual_qr_url) {
                            $('#manualQrPreview').attr(
                                'src',
                                res.manual_qr_url + '?t=' + Date.now()
                            );
                        }
                    },

                    error: function(xhr) {

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {

                            Object.values(xhr.responseJSON.errors)
                                .forEach(messages => {
                                    toastr.error(messages[0]);
                                });

                        } else {

                            toastr.error(
                                xhr.responseJSON?.message ||
                                'មានបញ្ហាក្នុងការរក្សាទុក'
                            );
                        }
                    },

                    complete: function() {

                        $('button[type="submit"][form="qrSettingForm"]')
                            .prop('disabled', false)
                            .html('<i class="fas fa-save mr-2"></i> រក្សាទុក');
                    }
                });
            });

        });
    </script>
@stop
