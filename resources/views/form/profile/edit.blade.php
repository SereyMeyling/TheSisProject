@extends('adminlte::page')

@section('title', 'ព័ត៌មានផ្ទាល់ខ្លួន')

@section('content')

<div class="toast-container-custom" id="toastContainer"></div>

<div class="row mt-3">
    <!-- LEFT: Avatar + basic identity card -->
    <div class="col-md-4">
        <div class="card profile-card text-center">
            <div class="card-body">
                <div class="avatar-wrap">
                <img id="avatarPreview"
    src="{{ $user->avatar ? route('profile.avatar', $user->id) . '?v=' . $user->updated_at->timestamp : asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}"
    class="avatar-img">
                    <label for="avatarInput" class="avatar-edit-btn" title="ប្តូររូបភាព">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <h5 class="mt-3 mb-0 font-weight-bold">{{ $user->name }}</h5>
                <span class="badge badge-success-soft">{{ ucfirst($user->getRoleNames()->first() ?? '—') }}</span>
                <p class="text-muted small mt-2 mb-0">{{ $user->email }}</p>
            </div>
        </div>

        <div class="card profile-card mt-3">
            <div class="card-body">
                <h6 class="font-weight-bold mb-3"><i class="fas fa-shield-alt text-success mr-1"></i> សុវត្ថិភាពគណនី
                </h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">Two-Factor Authentication</span>
                    @if($user->google2fa_enabled)
                        <span class="badge badge-success">បើក</span>
                    @else
                        <span class="badge badge-secondary">បិទ</span>
                    @endif
                </div>
                <p class="text-muted small mb-0">2FA ជាការចាំបាច់សម្រាប់គណនីទាំងអស់ក្នុងប្រព័ន្ធនេះ។</p>
            </div>
        </div>
    </div>

    <!-- RIGHT: Editable info + password -->
    <div class="col-md-8">
        <div class="card profile-card">
            <div class="card-header bg-white font-weight-bold">ព័ត៌មានទូទៅ</div>
            <div class="card-body">
                <form id="profileInfoForm" action="{{ route('profile.update') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">ឈ្មោះ (Full Name)</label>
                            <input type="text" name="name" id="name" class="form-control"
                                value="{{ old('name', $user->name) }}" required>
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">អ៊ីមែល (Email)</label>
                            <input type="email" name="email" id="email" class="form-control"
                                value="{{ old('email', $user->email) }}" required>
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">ឈ្មោះអ្នកប្រើប្រាស់ (Username)</label>
                            <input type="text" name="username" id="username" class="form-control"
                                value="{{ old('username', $user->username) }}" required>
                            <div class="invalid-feedback" id="error_username"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">លេខទូរស័ព្ទ (Phone)</label>
                            <input type="text" name="phone" id="phone" class="form-control"
                                value="{{ old('phone', $user->phone) }}" placeholder="0XX XXX XXXX">
                            <div class="invalid-feedback" id="error_phone"></div>
                        </div>
                    </div>

                    <div class="card profile-card mt-3">
    <div class="card-header bg-white font-weight-bold">លក្ខណសម្បត្តិវិជ្ជាជីវៈ (Professional Credentials)</div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold small">ដេប៉ាតឺម៉ង់ (Department)</label>
                <select name="department_id" id="department_id" class="form-control">
                    <option value="">-- ជ្រើសរើសដេប៉ាតឺម៉ង់ --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}"
                            {{ old('department_id', $user->department_id) == $dept->department_id ? 'selected' : '' }}>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="error_department_id"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="font-weight-bold small">ជំនាញ (Specialization)</label>
                <input type="text" name="specialization" id="specialization" class="form-control"
                       value="{{ old('specialization', $user->specialization) }}"
                       placeholder="ឧ. ជំងឺទូទៅ, ជំងឺផ្លូវដង្ហើម, ជំងឺស្បែក, ជំងឺផ្លូវចិត្ត">
                <div class="invalid-feedback" id="error_specialization"></div>
            </div>
        </div>
    </div>
</div>

                    <button type="submit" class="btn btn-primary px-4" id="btnSaveProfile">
                        <i class="fas fa-save mr-1"></i> រក្សាទុកព័ត៌មាន
                    </button>
                </form>
            </div>
        </div>

        <div class="card profile-card mt-3">
            <div class="card-header bg-white font-weight-bold">ផ្លាស់ប្តូរពាក្យសម្ងាត់</div>
            <div class="card-body">
                <form id="profilePasswordForm" action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">ពាក្យសម្ងាត់បច្ចុប្បន្ន</label>
                            <input type="password" name="current_password" id="current_password" class="form-control"
                                required>
                            <div class="invalid-feedback" id="error_current_password"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">ពាក្យសម្ងាត់ថ្មី</label>
                            <input type="password" name="password" id="password" class="form-control" minlength="8"
                                required>
                            <div class="invalid-feedback" id="error_password"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold small">បញ្ជាក់ពាក្យសម្ងាត់ថ្មី</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary px-4" id="btnSavePassword">
                        <i class="fas fa-key mr-1"></i> ផ្លាស់ប្តូរពាក្យសម្ងាត់
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .profile-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .05);
    }

    .avatar-wrap {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto;
    }

    .avatar-img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f0f2f4;
    }

    .avatar-edit-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        background: #198754;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin: 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .2);
        background: var(--primary-color);
    }

    .badge-success-soft {

        font-weight: 500;
        background: #e6f4ec;
        color: var(--primary-color);
    }
</style>
@stop

@section('js')
@parent
<script>
    $(document).ready(function () {

       function showToast(msg, type = 'success') {
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    const $toast = $('<div class="toast-custom ' + type + '"><i class="fas ' + icon + '"></i><span>' + msg + '</span></div>');
    $('#toastContainer').append($toast);
    setTimeout(function () {
        $toast.fadeOut(300, function () { $toast.remove(); });
    }, 4000);
}

        function clearErrors($form) {
            $form.find('.form-control').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
        }

        function showErrors($form, errors) {
            $.each(errors, function (field, messages) {
                $('#' + field).addClass('is-invalid');
                $('#error_' + field).text(messages[0]);
            });
        }

        // Preview avatar immediately + auto-submit info form
        $('#avatarInput').on('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => $('#avatarPreview').attr('src', e.target.result);
            reader.readAsDataURL(file);
        });

        // ---- Update profile info ----
        $('#profileInfoForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSaveProfile');
            clearErrors($form);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងរក្សាទុក...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    showToast(res.message || 'បានរក្សាទុក');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                    } else {
                        alert('មានបញ្ហាកើតឡើង សូមព្យាយាមម្តងទៀត');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> រក្សាទុកព័ត៌មាន');
                }
            });
        });

        // ---- Update password ----
        $('#profilePasswordForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btnSavePassword');
            clearErrors($form);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> កំពុងផ្លាស់ប្តូរ...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    showToast(res.message || 'បានផ្លាស់ប្តូរពាក្យសម្ងាត់');
                    $form[0].reset();
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                    } else {
                        alert('មានបញ្ហាកើតឡើង សូមព្យាយាមម្តងទៀត');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-key mr-1"></i> ផ្លាស់ប្តូរពាក្យសម្ងាត់');
                }
            });
        });
    });
</script>
@stop
