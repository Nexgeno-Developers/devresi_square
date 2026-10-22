@once
    <style>
        .pw-wrap { position: relative; display: block; }
        .auth-field .pw-wrap > input,
        .pw-wrap > .form-control {
            padding-right: 2.75rem;
        }
        .pw-wrap > .form-control.is-invalid,
        .pw-wrap > .form-control.is-valid {
            background-image: none;
        }
        .pw-wrap:has(.is-invalid) + .invalid-feedback { display: block; }
        .pw-eye {
            position: absolute;
            top: 0;
            right: 0;
            width: 44px;
            height: 100%;
            border: 0;
            background: transparent;
            color: #5c6578;
            cursor: pointer;
            display: grid;
            place-items: center;
            padding: 0;
        }
        .pw-eye:hover,
        .pw-eye:focus-visible { color: #0f173a; }
        .pw-eye:focus-visible {
            outline: 3px solid #ff5c1d;
            outline-offset: -3px;
            border-radius: 8px;
        }
        .pw-eye svg { width: 20px; height: 20px; display: block; }
        .pw-eye .pw-eye-off { display: none; }
        .pw-eye.is-shown .pw-eye-on { display: none; }
        .pw-eye.is-shown .pw-eye-off { display: block; }
    </style>
    <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.pw-eye');
            if (!button) return;
            var input = button.closest('.pw-wrap')?.querySelector('input');
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-shown', show);
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    </script>
@endonce
<button type="button" class="pw-eye" aria-label="Show password" aria-pressed="false">
    <svg class="pw-eye-on" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="pw-eye-off" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 3l18 18"/>
        <path d="M10.6 10.6a3 3 0 004.2 4.2"/>
        <path d="M9.9 5.2A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a18.2 18.2 0 01-3.2 3.8"/>
        <path d="M6.1 6.1C3.8 7.8 2 12 2 12s3.5 6 10 6c1.1 0 2.1-.2 3.1-.5"/>
    </svg>
</button>
