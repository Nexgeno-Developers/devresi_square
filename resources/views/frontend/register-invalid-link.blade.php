@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card bg-light text-center" style="border-radius: 1rem;">
                    <div class="card-body p-md-5 p-4">

                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                        </div>

                        <h3 class="fw-bold mb-3">Invalid or Expired Link</h3>

                        <p class="mb-3">
                            This verification link is invalid or has already been used.
                        </p>

                        <a href="{{ route('register') }}" class="btn btn_outline_secondary mt-2">
                            Register Again
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
