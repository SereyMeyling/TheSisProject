<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page-title', config('adminlte.title', 'Hospital Management System'))</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    {{-- Google Fonts: Khmer + Latin support --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    {{-- AdminLTE / Bootstrap base (kept for shared classes & consistency with the rest of the app) --}}
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    {{-- Custom hospital auth theme (login + 2FA pages) --}}
    <link rel="stylesheet" href="{{ asset('css/auth/hospital-login.css') }}">

    @stack('styles')
</head>

<body class="hms-login-body">

    <div class="hms-card">
        <div class="hms-card-accent"></div>

        {{-- Logo --}}
        <div class="hms-logo-wrap">
            <img src="{{ $setting && $setting->logo
    ? asset('storage/' . $setting->logo)
    : asset('vendor/adminlte/dist/img/logo.jpg')
    }}" alt="Hospital Logo">

        </div>

        <h1 class="hms-title">{{ $setting->system_name ?? 'មន្ទីរសម្រាកព្យាបាលព្រំ សន្តិភាព' }}
        </h1>

        @hasSection('auth-subtitle')
            <p class="hms-auth-subtitle">@yield('auth-subtitle')</p>
        @endif

        {{-- Page-specific content: forms, alerts, etc. --}}
        @yield('auth-content')
    </div>

    <div class="hms-page-footer">
        <div>&copy; {{ date('Y') }} {{ $setting->system_name ?? 'មន្ទីរសម្រាកព្យាបាលព្រំ សន្តិភាព' }} ||  All rights reserved.</div>
        <div class="hms-footer-links">
            <a href="#">Privacy Policy</a>
            <span class="hms-dot">&middot;</span>
            <a href="#">Terms of Service</a>
        </div>
    </div>

    {{-- jQuery (bundled with AdminLTE assets) --}}
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    @stack('scripts')
</body>

</html>
