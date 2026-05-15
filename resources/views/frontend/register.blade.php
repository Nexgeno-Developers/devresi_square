@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-light" style="border-radius: 1rem;">
                    <div class="card-body p-md-4 p-4 text-center">

                        {{-- Step indicator --}}
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-4">
                            <div class="step-dot active" id="dot-1">
                                <span class="step-num">1</span>
                                <span class="step-label">Details</span>
                            </div>
                            <div class="step-line" id="step-line"></div>
                            <div class="step-dot" id="dot-2">
                                <span class="step-num">2</span>
                                <span class="step-label">Verify</span>
                            </div>
                        </div>

                        {{-- ══ STEP 1 ══ --}}
                        <div id="step-1">
                            <h2 class="fw-bold mb-1 text-uppercase">Create Account</h2>
                            <p class="mb-4 text-muted small">Fill in your details to get started</p>

                            <div id="reg-errors" class="alert alert-danger text-start d-none"></div>

                            <form id="reg-form" novalidate>
                                @csrf
                                <div class="row g-3 text-start">

                                    <div class="col-md-6">
                                        <input type="text" class="form-control form-control-lg"
                                               id="first_name" name="first_name"
                                               placeholder="First Name" required />
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <input type="text" class="form-control form-control-lg"
                                               id="last_name" name="last_name"
                                               placeholder="Last Name" required />
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-12">
                                        <input type="email" class="form-control form-control-lg"
                                               id="email" name="email"
                                               placeholder="Email Address" required />
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-12">
                                        <input type="tel" class="form-control form-control-lg"
                                               id="phone" name="phone"
                                               placeholder="Phone Number (required for Phone OTP)" />
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-12">
                                        <select class="form-select form-select-lg" id="type" name="type" required>
                                            <option value="" disabled selected>I am a...</option>
                                            <option value="landlord">Landlord</option>
                                            <option value="owner">Owner</option>
                                            <option value="estate_agent">Estate Agent</option>
                                            <option value="contractor">Contractor</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold mb-2 d-block text-start">
                                            Verify via
                                        </label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                       name="verify_via" id="via-email" value="email" checked>
                                                <label class="form-check-label" for="via-email">
                                                    <i class="fas fa-envelope me-1"></i> Email OTP
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                       name="verify_via" id="via-phone" value="phone">
                                                <label class="form-check-label" for="via-phone">
                                                    <i class="fas fa-mobile-alt me-1"></i> Phone OTP
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <button type="submit" id="reg-submit-btn"
                                        class="btn btn_outline_secondary btn-lg w-100 mt-4">
                                    Send Verification Code
                                </button>
                            </form>
                        </div>

                        {{-- ══ STEP 2 ══ --}}
                        <div id="step-2" style="display:none;">
                            <h2 class="fw-bold mb-1 text-uppercase">Enter OTP</h2>
                            <p class="mb-1 text-muted small" id="otp-sent-msg"></p>

                            {{-- 2-minute countdown --}}
                            <p class="mb-3" id="timer-wrap">
                                Code expires in <span id="countdown" class="fw-bold text-danger">2:00</span>
                            </p>

                            <div id="otp-error" class="alert alert-danger d-none text-start"></div>
                            <div id="otp-success" class="alert alert-success d-none text-start"></div>

                            <form id="otp-form" action="{{ route('register.verify.otp.post') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <input type="text"
                                           class="form-control form-control-lg text-center fw-bold"
                                           id="otp-input" name="otp"
                                           placeholder="_ _ _ _ _ _"
                                           maxlength="6"
                                           autocomplete="one-time-code"
                                           inputmode="numeric"
                                           pattern="[0-9]{6}"
                                           style="font-size:2rem; letter-spacing:10px;"
                                           required />
                                </div>

                                <button type="submit" class="btn btn_outline_secondary btn-lg w-100">
                                    Verify &amp; Continue
                                </button>
                            </form>

                            <div class="mt-3 d-flex justify-content-center gap-3 flex-wrap">
                                <button type="button" id="resend-btn"
                                        class="btn btn-link btn-sm p-0 text-muted" disabled>
                                    Didn't receive it? Resend OTP
                                </button>
                                <span class="text-muted">|</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                                        id="change-details-btn">
                                    &larr; Change details
                                </button>
                            </div>
                        </div>

                        <div class="mt-3" id="login-link">
                            <p class="mb-0 small">Already have an account?
                                <a href="{{ route('login') }}" class="fw-bold">Login</a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.step-dot { display:flex; flex-direction:column; align-items:center; gap:4px; }
.step-num {
    width:32px; height:32px; border-radius:50%;
    background:#dee2e6; color:#6c757d;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:14px; transition:all .3s;
}
.step-dot.active .step-num { background:#0b60bd; color:#fff; }
.step-dot.done   .step-num { background:#198754; color:#fff; }
.step-label { font-size:11px; color:#6c757d; }
.step-line  { flex:1; height:2px; background:#dee2e6; min-width:40px; }
</style>

@section('page.scripts')
<script>
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    let countdownInterval = null;
    let verifyVia         = 'email';
    let csrfToken         = document.querySelector('meta[name="csrf-token"]')?.content
                         || document.querySelector('#reg-form [name="_token"]')?.value
                         || '';

    // ── DOM refs ───────────────────────────────────────────────────────────
    const step1       = document.getElementById('step-1');
    const step2       = document.getElementById('step-2');
    const loginLink   = document.getElementById('login-link');
    const dot1        = document.getElementById('dot-1');
    const dot2        = document.getElementById('dot-2');
    const regForm     = document.getElementById('reg-form');
    const submitBtn   = document.getElementById('reg-submit-btn');
    const regErrors   = document.getElementById('reg-errors');
    const otpSentMsg  = document.getElementById('otp-sent-msg');
    const otpError    = document.getElementById('otp-error');
    const otpSuccess  = document.getElementById('otp-success');
    const otpInput    = document.getElementById('otp-input');
    const resendBtn   = document.getElementById('resend-btn');
    const changeBtn   = document.getElementById('change-details-btn');
    const countdown   = document.getElementById('countdown');

    // ── Helpers ────────────────────────────────────────────────────────────
    function showFieldError(name, msg) {
        const el = regForm.querySelector('[name="' + name + '"]');
        if (!el) return;
        el.classList.add('is-invalid');
        const fb = el.nextElementSibling;
        if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = msg;
    }

    function clearFieldErrors() {
        regForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        regForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        regErrors.classList.add('d-none');
        regErrors.innerHTML = '';
    }

    function showOtpError(msg) {
        otpError.textContent = msg;
        otpError.classList.remove('d-none');
        otpSuccess.classList.add('d-none');
    }

    function showOtpSuccess(msg) {
        otpSuccess.textContent = msg;
        otpSuccess.classList.remove('d-none');
        otpError.classList.add('d-none');
    }

    // ── Countdown timer ────────────────────────────────────────────────────
    function startCountdown(seconds) {
        clearInterval(countdownInterval);
        resendBtn.disabled = true;

        let remaining = seconds;
        function tick() {
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            countdown.textContent = m + ':' + String(s).padStart(2, '0');
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                countdown.textContent = '0:00';
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend OTP';
            }
            remaining--;
        }
        tick();
        countdownInterval = setInterval(tick, 1000);
    }

    // ── Show step 2 ────────────────────────────────────────────────────────
    function showStep2(via) {
        verifyVia = via;
        step1.style.display     = 'none';
        step2.style.display     = 'block';
        loginLink.style.display = 'none';

        dot1.classList.remove('active');
        dot1.classList.add('done');
        dot2.classList.add('active');

        otpSentMsg.textContent = via === 'email'
            ? 'We\'ve sent a 6-digit code to your email address.'
            : 'We\'ve sent a 6-digit code to your phone number.';

        otpError.classList.add('d-none');
        otpSuccess.classList.add('d-none');
        otpInput.value = '';

        startCountdown(120); // 2 minutes
        setTimeout(() => otpInput.focus(), 100);
    }

    // ── Go back to step 1 ──────────────────────────────────────────────────
    function goBackToStep1() {
        clearInterval(countdownInterval);
        step2.style.display     = 'none';
        step1.style.display     = 'block';
        loginLink.style.display = 'block';

        dot1.classList.add('active');
        dot1.classList.remove('done');
        dot2.classList.remove('active');

        // Reset submit button fully
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Send Verification Code';
        clearFieldErrors();
    }

    // ── Register form submit ───────────────────────────────────────────────
    regForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearFieldErrors();

        const formData = new FormData(regForm);
        const via      = formData.get('verify_via');

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Sending…';

        fetch('{{ route("register.post") }}', {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken,
            },
            body: formData,
        })
        .then(r => r.json().then(data => ({ status: r.status, data })))
        .then(({ status, data }) => {
            if (data.success) {
                showStep2(via);
            } else {
                submitBtn.disabled    = false;
                submitBtn.textContent = 'Send Verification Code';

                if (data.errors) {
                    let hasField = false;
                    Object.entries(data.errors).forEach(([field, msgs]) => {
                        showFieldError(field, msgs[0]);
                        hasField = true;
                    });
                    if (!hasField) {
                        regErrors.innerHTML = '<ul class="mb-0">' +
                            Object.values(data.errors).flat()
                                .map(m => '<li>' + m + '</li>').join('') +
                            '</ul>';
                        regErrors.classList.remove('d-none');
                    }
                } else {
                    regErrors.textContent = data.message || 'Something went wrong.';
                    regErrors.classList.remove('d-none');
                }
            }
        })
        .catch(() => {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Send Verification Code';
            regErrors.textContent = 'Network error. Please try again.';
            regErrors.classList.remove('d-none');
        });
    });

    // ── Resend OTP (AJAX) ──────────────────────────────────────────────────
    resendBtn.addEventListener('click', function () {
        resendBtn.disabled    = true;
        resendBtn.textContent = 'Sending…';
        otpError.classList.add('d-none');
        otpSuccess.classList.add('d-none');

        fetch('{{ route("register.resend.otp") }}', {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showOtpSuccess(data.message || 'OTP resent successfully.');
                startCountdown(120);
                otpInput.value = '';
                otpInput.focus();
            } else {
                showOtpError(data.message || 'Failed to resend OTP.');
                resendBtn.disabled    = false;
                resendBtn.textContent = 'Resend OTP';
            }
        })
        .catch(() => {
            showOtpError('Network error. Please try again.');
            resendBtn.disabled    = false;
            resendBtn.textContent = 'Resend OTP';
        });
    });

    // ── Change details button ──────────────────────────────────────────────
    changeBtn.addEventListener('click', goBackToStep1);

    // ── Phone field hint based on verify_via ──────────────────────────────
    document.querySelectorAll('[name="verify_via"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const phoneInput = document.getElementById('phone');
            if (this.value === 'phone') {
                phoneInput.placeholder = 'Phone Number (required)';
                phoneInput.setAttribute('required', '');
            } else {
                phoneInput.placeholder = 'Phone Number (optional)';
                phoneInput.removeAttribute('required');
            }
        });
    });

})();
</script>
@endsection
@endsection

