@extends('layouts.auth')

@section('title', 'Check your email · Resisquare')
@section('shell_class', 'auth-compact')

@section('content')
    <p class="auth-mark">{{ get_setting('website_name') ?: 'Resisquare' }}</p>
    <h2>Check your email</h2>
    <p class="auth-muted">Enter the 6-digit code sent to <strong>{{ $registration->email }}</strong>.</p>

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.verify.otp.post') }}">
        @csrf
        <label class="auth-field">
            <span>Code</span>
            <input name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
        </label>
        <button class="auth-btn" type="submit">Verify and continue</button>
    </form>

    <form method="POST" action="{{ route('register.resend.otp') }}">
        @csrf
        <button class="auth-link" type="submit" style="background:none;border:0;padding:0;cursor:pointer;">Resend code</button>
    </form>
@endsection
