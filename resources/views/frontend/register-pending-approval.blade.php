@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card bg-light text-center" style="border-radius: 1rem;">
                    <div class="card-body p-md-5 p-4">

                        <div class="mb-4">
                            <i class="fas fa-clock fa-4x text-warning"></i>
                        </div>

                        <h3 class="fw-bold mb-3">Registration Under Review</h3>

                        <p class="mb-3">
                            Thank you! Your registration has been submitted and is currently
                            being reviewed by our team.
                        </p>

                        <p class="mb-3">
                            Once approved, you'll receive an email with a link to set your
                            password and access your account.
                        </p>

                        <p class="text-muted small">
                            This usually takes 1–2 business days.
                        </p>

                        <a href="{{ route('home') }}" class="btn btn_outline_secondary mt-3">
                            Back to Home
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
