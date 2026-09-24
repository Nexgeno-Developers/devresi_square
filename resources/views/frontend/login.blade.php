@extends('layouts.auth')

@section('title', 'Sign in · Resisquare')
@section('brand_headline', 'Homes, rent and repairs in one workspace.')
@section('brand_copy', 'Landlords see the portfolio. Tenants only see their home.')

@section('content')
    <h2>Sign in</h2>
    <p class="auth-muted">Use the email for your Resisquare account.</p>

    @include('auth.partials.login-errors')

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <label class="auth-field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </label>
        <label class="auth-field">
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <label class="auth-check">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
        </label>
        <button type="submit" class="auth-btn">Login</button>
    </form>

    <p class="auth-muted">
        <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
    </p>
    <p class="auth-muted mb-0">
        Don’t have an account?
        <a class="auth-link" href="{{ route('register') }}">Sign up</a>
    </p>
@endsection
