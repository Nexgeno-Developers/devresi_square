<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Resisquare')</title>
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">
    <link href="{{ asset('asset/backend/css/auth.css') }}?v={{ @filemtime(public_path('asset/backend/css/auth.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="auth-body">
    <div class="auth-shell">
        <div class="auth-login">
            <div class="auth-brand">
                <p class="auth-kicker">{{ get_setting('website_name') ?: 'Resisquare' }}</p>
                <h1>@yield('brand_headline', 'Homes, rent and repairs in one workspace.')</h1>
                <p>@yield('brand_copy', 'Landlords see the portfolio. Tenants only see their home.')</p>
            </div>
            <div class="auth-form">
                @yield('content')
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
