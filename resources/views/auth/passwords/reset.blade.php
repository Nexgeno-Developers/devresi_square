@extends('layouts.auth')

@section('title', 'Choose a new password · Resisquare')
@section('brand_headline', 'Choose a new password.')
@section('brand_copy', 'Use at least 8 characters. You’ll sign in with this next time.')

@section('content')
    <h2>Reset password</h2>
    <p class="auth-muted">Almost done — set a new password for {{ $email ?? 'your account' }}.</p>

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

    <form method="POST" action="{{ route('password.reset') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if(! empty($email))
            <input type="hidden" name="email" value="{{ $email }}">
        @endif
        <label class="auth-field">
            <span>New password</span>
            <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password">
        </label>
        <label class="auth-field">
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="auth-btn">Save password</button>
    </form>

    <p class="auth-muted mb-0">
        <a class="auth-link" href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection
