@extends('layouts.auth')

@section('title', 'Claim your contractor account')
@section('shell_class', 'auth-compact')

@section('content')
    <p class="auth-mark">{{ get_setting('website_name') ?: 'Resisquare' }} for contractors</p>
    <h2>Claim your account</h2>
    <p class="auth-muted">Hi {{ $user->first_name ?: $user->name }}. We have a new way of sending repairs. Set a password, then add your documents. About a minute.</p>

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf
        <label class="auth-field">
            <span>Email</span>
            <input value="{{ $user->email }}" disabled>
        </label>
        <label class="auth-field">
            <span>New password</span>
            <input type="password" name="password" autocomplete="new-password" minlength="8" required autofocus>
        </label>
        <label class="auth-field">
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
        </label>
        <label class="auth-check">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
            <span>I accept the <a class="auth-link" href="{{ route('contractor.terms') }}" target="_blank" rel="noopener">contractor terms and privacy notice</a></span>
        </label>
        <button class="auth-btn" type="submit">Continue</button>
    </form>
@endsection
