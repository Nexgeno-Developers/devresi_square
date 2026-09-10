@extends('frontend.layout.app')

@section('title', 'Login | Resisquare')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Login</h2>
        @include('auth.partials.login-errors')
        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                    name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" autocomplete="current-password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" checked>
                <label class="form-check-label" for="remember">Remember Me</label>
            </div>
            <div class="mb-3">
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>
@endsection
