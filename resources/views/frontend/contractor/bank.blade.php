@extends('layouts.auth')

@section('title', 'Payment details · Resisquare')
@section('shell_class', 'auth-compact')

@section('content')
    <p class="auth-mark">Payment details</p>
    <h2>Where the landlord pays you</h2>
    <p class="auth-muted">These details are printed on your invoices. ResiSquare does not take the payment.</p>

    @if(session('success'))
        <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="auth-alert auth-alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('contractor.profile.bank.update') }}">
        @csrf
        <label class="auth-field">
            <span>Account name</span>
            <input name="bank_account_name" value="{{ old('bank_account_name', $profile->bank_account_name) }}" required maxlength="191" autocomplete="organization">
        </label>
        <label class="auth-field">
            <span>Sort code</span>
            <input name="bank_sort_code" value="{{ old('bank_sort_code', $profile->formattedSortCode()) }}" required inputmode="numeric" placeholder="11-22-33" autocomplete="off">
        </label>
        <label class="auth-field">
            <span>Account number</span>
            <input name="bank_account_number" value="{{ old('bank_account_number', $profile->bank_account_number) }}" required inputmode="numeric" maxlength="8" placeholder="12345678" autocomplete="off">
        </label>
        <label class="auth-field">
            <span>Bank name (optional)</span>
            <input name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}" maxlength="191">
        </label>
        <button class="auth-btn" type="submit">Save payment details</button>
    </form>
    <p class="auth-muted"><a class="auth-link" href="{{ route('contractor.jobs.index') }}">Back to jobs</a></p>
@endsection
