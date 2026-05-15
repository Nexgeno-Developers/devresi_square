@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card bg-light text-center" style="border-radius: 1rem;">
                    <div class="card-body p-md-5 p-4">

                        <div class="mb-4">
                            <i class="fas fa-envelope-open-text fa-4x text-primary"></i>
                        </div>

                        <h3 class="fw-bold mb-3">Check Your Email</h3>

                        <p class="mb-3">
                            We've sent a verification link to your email address.
                            Please click the link to verify your email and continue.
                        </p>

                        <p class="text-muted small">
                            Didn't receive it? Check your spam folder, or
                            <a href="{{ route('register') }}">register again</a>.
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
