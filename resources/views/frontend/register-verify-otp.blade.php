@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card bg-light text-center" style="border-radius: 1rem;">
                    <div class="card-body p-md-5 p-4">

                        <div class="mb-4">
                            @if($registration->verify_via === 'email')
                                <i class="fas fa-envelope-open-text fa-4x text-primary"></i>
                            @else
                                <i class="fas fa-mobile-alt fa-4x text-primary"></i>
                            @endif
                        </div>

                        <h3 class="fw-bold mb-2">Enter Verification Code</h3>
                        <p class="mb-4 text-muted">
                            @if($registration->verify_via === 'email')
                                We sent a 6-digit code to <strong>{{ $registration->email }}</strong>.
                            @else
                                We sent a 6-digit code to <strong>{{ $registration->phone }}</strong>.
                            @endif
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger text-start">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form action="{{ route('register.verify.otp.post') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <input type="text"
                                       class="form-control form-control-lg text-center fw-bold"
                                       name="otp"
                                       placeholder="_ _ _ _ _ _"
                                       maxlength="6"
                                       autocomplete="one-time-code"
                                       inputmode="numeric"
                                       pattern="[0-9]{6}"
                                       style="font-size:2rem; letter-spacing:10px;"
                                       required autofocus />
                            </div>

                            <button class="btn btn_outline_secondary btn-lg w-100" type="submit">
                                Verify &amp; Continue
                            </button>
                        </form>

                        <div class="mt-3">
                            <form action="{{ route('register.resend.otp') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0 text-muted">
                                    Didn't receive it? Resend OTP
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
