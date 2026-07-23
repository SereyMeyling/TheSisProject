@extends('layouts.auth')

@section('page-title', 'Verify Code')

{{-- Uses the shared hospital name/logo from layouts.auth by default.
     Override here if you want a different heading on this page. --}}
@section('auth-subtitle', 'Enter your authentication code')

@section('auth-content')

    {{-- Session / general errors (e.g. invalid code) --}}
    @if (session('error'))
        <div class="hms-alert hms-alert-danger">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- Validation errors (e.g. missing / malformed code) --}}
    @error('one_time_password')
        <div class="hms-alert hms-alert-danger">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form method="POST" action="{{ route('2fa.verify.submit') }}" id="hms-2fa-form" novalidate>
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

        <button type="submit" class="hms-submit-btn" id="hms-2fa-submit-btn">
            <span class="hms-btn-label">Verify &nbsp;<i class="fas fa-shield-halved"></i></span>
            <span class="hms-spinner"></span>
        </button>
    </form>

    <div class="hms-secondary-action">
        <form method="POST" action="{{ route('logout') }}" id="hms-logout-form">
            @csrf
            <button type="submit" class="hms-secondary-link">Not you? Log out</button>
        </form>
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        // Digits-only input, auto-submit friendly formatting
        var otpInput = document.querySelector('.hms-otp-input');
        if (otpInput) {
            otpInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
            });
        }

        // Loading animation on submit
        var form = document.getElementById('hms-2fa-form');
        var submitBtn = document.getElementById('hms-2fa-submit-btn');

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