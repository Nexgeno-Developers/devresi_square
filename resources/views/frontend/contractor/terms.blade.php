@extends('layouts.auth')

@section('title', 'Contractor terms and privacy · Resisquare')
@section('shell_class', 'auth-onboard')

@section('content')
    <p class="auth-mark">{{ get_setting('website_name') ?: 'Resisquare' }} for contractors</p>
    <h2>Contractor terms and privacy notice</h2>
    <p class="auth-muted">Version {{ config('contractor_marketplace.privacy_version') }}. Applies to work on properties in England.</p>

    <h3 class="auth-h3">Using the platform</h3>
    <p>You join free. You are self-employed or a company, not our employee, and you set your own prices. Landlords choose who to instruct. We do not deduct or report CIS; the landlord or their accountant decides how CIS applies.</p>
    <p>You must hold the insurance and registrations your trades need, including Gas Safe for gas work and a competent person scheme for electrical work, and keep them in date. We pause your account if they lapse.</p>
    <p>Prices you send must show the total a landlord will pay, including VAT if you are VAT registered. Work outside the agreed scope is not billable unless the landlord approves it.</p>

    <h3 class="auth-h3">Your data</h3>
    <p>We use your name, contact details, business details and documents to check you can do the work you offer (legitimate interests and to take steps towards a contract with landlords). Photo ID and certificates are stored encrypted and only our compliance reviewers can open them. Landlords see badges, such as “Insured until Mar 2027”, never the files.</p>
    <p>Before a landlord accepts your offer you see the area and the issue, not the tenant’s name, phone or full address. After acceptance you receive what you need to attend.</p>
    <p>We keep documents while your account is active, and delete files from rejected or removed accounts after {{ \App\Support\ContractorSettings::get('document_retention_days') }} days. You can ask us for a copy of your data or to delete it by contacting support.</p>

    <p class="auth-muted">This notice is a draft pending legal review.</p>
    <a class="auth-link" href="{{ route('register.contractor') }}">Back to sign up</a>
@endsection
