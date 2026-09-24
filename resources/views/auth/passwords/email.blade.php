@extends('layouts.auth')

@section('title', 'Reset password · Resisquare')
@section('brand_headline', 'Reset your password.')
@section('brand_copy', 'We’ll email a secure link. No new account is created.')

@section('content')
    <h2>Forgot password</h2>
    <p class="auth-muted">Enter the email on your account.</p>

    @if (session('status'))
        <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label class="auth-field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <button type="submit" class="auth-btn">Send reset link</button>
    </form>

    <p class="auth-muted mb-0">
        <a class="auth-link" href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection
