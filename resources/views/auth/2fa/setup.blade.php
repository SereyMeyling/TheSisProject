@extends('layouts.auth')

@section('page-title', 'Setup Two-Factor Authentication')

@section('auth-subtitle')
    Scan the QR code with Google Authenticator, then enter the 6-digit code to confirm.
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

    <p class="hms-manual-key">Can't scan? Enter this key manually:<br><code>{{ $secret }}</code></p>

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
                    placeholder="6-digit code"
                    required
                    autofocus
                >
            </div>
        </div>

        <button type="submit" class="hms-submit-btn" id="hms-2fa-setup-submit-btn">
            <span class="hms-btn-label">Confirm &amp; Continue &nbsp;<i class="fas fa-shield-halved"></i></span>
            <span class="hms-spinner"></span>
        </button>
    </form>

    <div class="hms-secondary-action">
        <form method="POST" action="{{ route('logout') }}" id="hms-logout-form">
            @csrf
            <button type="submit" class="hms-secondary-link">Log out</button>
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