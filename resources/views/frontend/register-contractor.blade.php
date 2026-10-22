@extends('layouts.auth')

@section('title', 'Contractor sign up · Resisquare')
@section('brand_headline', 'Get repair work from landlords.')
@section('brand_copy', 'Free to join. Jobs, visits and invoices stay on your account.')
@section('form_class', 'auth-form-start')

@section('content')
    <h2>Create account</h2>
    @if($invite)
        <p class="auth-muted">You were invited by a landlord. Sign up free to receive their jobs.</p>
    @else
        <p class="auth-muted">Free. About a minute.</p>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert-danger">
            {{ $errors->first() }}
            @if($errors->has('email') && str_contains($errors->first('email'), 'Log in'))
                <a class="auth-link" href="{{ route('login') }}">Log in</a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('register.contractor.post') }}">
        @csrf
        <label class="auth-field">
            <span>Full name</span>
            <input name="name" value="{{ old('name', $invite?->name) }}" autocomplete="name" required autofocus>
        </label>
        <label class="auth-field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email', $invite?->email) }}" autocomplete="email" required>
        </label>
        <label class="auth-field">
            <span>Mobile</span>
            <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required>
        </label>
        <label class="auth-field">
            <span>Password</span>
            <div class="pw-wrap">
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
                @include('auth.partials.password-eye')
            </div>
        </label>
        <label class="auth-check">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
            <span>I accept the <a class="auth-link" href="{{ route('contractor.terms') }}" target="_blank" rel="noopener">contractor terms and privacy notice</a></span>
        </label>
        <button class="auth-btn" type="submit">Create account</button>
    </form>

    <p class="auth-muted">Already registered? <a class="auth-link" href="{{ route('login') }}">Log in</a></p>
@endsection
