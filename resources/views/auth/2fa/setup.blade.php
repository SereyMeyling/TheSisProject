@extends('layouts.auth')

@section('page-title', 'Setup Two-Factor Authentication')

@section('auth-subtitle')
    សូមស្កេន QR Code ដោយប្រើកម្មវិធី Google Authenticator បន្ទាប់មកបញ្ចូលលេខកូដផ្ទៀងផ្ទាត់ ៦ ខ្ទង់ ដើម្បីបញ្ជាក់។
@endsection

@section('auth-content')

    @if (session('error'))
        <div class="hms-alert hms-alert-danger">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @error('one_time_password')
        <div class="hms-alert hms-alert-danger">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <div class="hms-qr-wrap">
        {!! $qrCodeSvg !!}
    </div>

    <p class="hms-manual-key">
    មិនអាចស្កេន QR Code បានទេ? សូមបញ្ចូលសោសម្ងាត់នេះ<br>
        <code>{{ $secret }}</code>
    </p>

    <form method="POST" action="{{ route('2fa.setup.confirm') }}" id="hms-2fa-setup-form" novalidate>
        @csrf

        <div class="hms-form-group">
            <div class="hms-input-group @error('one_time_password') is-invalid @enderror">
                <input
                    type="text"
                    name="one_time_password"
                    class="hms-otp-input"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="one-time-code"
                    placeholder="បញ្ចូលលេខកូដ ៦ ខ្ទង់"
                    required
                    autofocus
                >
            </div>
        </div>

        <button type="submit" class="hms-submit-btn" id="hms-2fa-setup-submit-btn">
            <span class="hms-btn-label">
                បញ្ជាក់ការផ្ទៀងផ្ទាត់<i class="fas fa-shield-halved"></i>
            </span>
            <span class="hms-spinner"></span>
        </button>
    </form>

    <div class="hms-secondary-action">
        <form method="POST" action="{{ route('logout') }}" id="hms-logout-form">
            @csrf
            <button type="submit" class="hms-secondary-link">
                ចាកចេញ
            </button>
        </form>
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        var otpInput = document.querySelector('.hms-otp-input');
        if (otpInput) {
            otpInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        }

        var form = document.getElementById('hms-2fa-setup-form');
        var submitBtn = document.getElementById('hms-2fa-setup-submit-btn');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                if (form.checkValidity && !form.checkValidity()) {
                    return;
                }
                submitBtn.classList.add('is-loading');
                submitBtn.disabled = true;
            });
        }
    })();
</script>
@endpush