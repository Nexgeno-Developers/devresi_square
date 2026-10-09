@extends('layouts.auth')

@section('title', 'Your business · Resisquare')
@section('shell_class', 'auth-onboard')

@section('content')
    @php
        $type = old('trading_type', $profile->trading_type);
        $picked = old('trades', $profile->trades ?? []);
        $radius = (int) old('coverage_radius_miles', $profile->coverage_radius_miles ?: 15);
        $vat = old('vat_registered', $profile->vat_registered ? '1' : ($profile->trading_type ? '0' : null));
    @endphp
    <div class="auth-progress" aria-hidden="true"><span class="is-on"></span><span class="is-on"></span><span></span></div>
    <p class="auth-mark">Step 2 of 3</p>
    <h2>Your business</h2>
    <p class="auth-muted">We use this to work out which documents you need. Nothing else.</p>

    @if(session('error') || $errors->any())
        <div class="auth-alert auth-alert-danger">
            {{ session('error') ?: $errors->first() }}
            @if($errors->has('company_number') && str_contains($errors->first('company_number'), 'Log in'))
                <a class="auth-link" href="{{ route('login') }}">Log in</a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('contractor.profile.business') }}">
        @csrf
        <div>
            <span class="auth-label">I work as</span>
            <div class="auth-choices">
                <label class="auth-choice"><input type="radio" name="trading_type" value="sole_trader" @checked($type === 'sole_trader') required><span>Sole trader</span></label>
                <label class="auth-choice"><input type="radio" name="trading_type" value="limited_company" @checked($type === 'limited_company')><span>Limited company</span></label>
            </div>
        </div>
        <div class="auth-row" data-when="limited" @if($type !== 'limited_company') hidden @endif>
            <label class="auth-field">
                <span>Companies House number</span>
                <input name="company_number" value="{{ old('company_number', $profile->company_number) }}" maxlength="8" autocapitalize="characters">
            </label>
            <label class="auth-field">
                <span>Trading name (optional)</span>
                <input name="trading_name" value="{{ old('trading_name', $profile->trading_name) }}">
            </label>
        </div>

        <div>
            <span class="auth-label">Trades</span>
            <div class="auth-chips">
                @foreach($trades as $key => $label)
                    <label class="auth-chip"><input type="checkbox" name="trades[]" value="{{ $key }}" @checked(in_array($key, $picked, true))><span>{{ $label }}</span></label>
                @endforeach
            </div>
        </div>

        <label class="auth-field">
            <span>Home postcode</span>
            <input name="postcode" value="{{ old('postcode', $profile->postcode) }}" autocomplete="postal-code" autocapitalize="characters" required>
        </label>
        <div>
            <span class="auth-label">I’ll travel up to</span>
            <div class="auth-choices">
                @foreach($radii as $miles)
                    <label class="auth-choice"><input type="radio" name="coverage_radius_miles" value="{{ $miles }}" @checked($radius === $miles)><span>{{ $miles }} miles</span></label>
                @endforeach
            </div>
        </div>

        <div>
            <span class="auth-label">VAT registered?</span>
            <div class="auth-choices">
                <label class="auth-choice"><input type="radio" name="vat_registered" value="0" @checked($vat === '0') required><span>No</span></label>
                <label class="auth-choice"><input type="radio" name="vat_registered" value="1" @checked($vat === '1')><span>Yes</span></label>
            </div>
        </div>
        <label class="auth-field" data-when="vat" @if($vat !== '1') hidden @endif>
            <span>VAT number</span>
            <input name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" placeholder="GB123456789" autocapitalize="characters">
        </label>

        <button class="auth-btn" type="submit">Continue</button>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.querySelector('form[action$="/contractor/profile/business"]');
        const sync = () => {
            const limited = form.querySelector('input[name="trading_type"]:checked')?.value === 'limited_company';
            const vat = form.querySelector('input[name="vat_registered"]:checked')?.value === '1';
            form.querySelectorAll('[data-when="limited"]').forEach(el => el.hidden = !limited);
            form.querySelectorAll('[data-when="vat"]').forEach(el => el.hidden = !vat);
        };
        form.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
