@extends('layouts.auth')

@section('title', 'Contractor terms and privacy · Resisquare')
@section('shell_class', 'auth-onboard auth-doc')

@push('styles')
    <style>
        /* A reading page: paragraph margins add to the card's grid gap, so drop them and space by section. */
        .auth-doc .auth-form { gap: 0.7rem; }
        .auth-doc .auth-form p { margin: 0; line-height: 1.55; }
        .auth-doc .auth-form .auth-muted { line-height: 1.45; }
        .auth-doc .auth-h3 { margin-top: 0.9rem; }
        .auth-doc .auth-link { justify-self: start; margin-top: 0.4rem; }

        @media (min-width: 801px) {
            .auth-doc { width: min(680px, 100%); }
            .auth-doc .auth-form { padding: 2rem 2.25rem 1.75rem; }
        }

        @media (max-width: 480px) {
            .auth-shell:has(.auth-doc) { padding: 0.75rem; }
        }
    </style>
@endpush

@section('content')
    <p class="auth-mark">{{ get_setting('website_name') ?: 'Resisquare' }} for contractors</p>
    <h2>Contractor terms and privacy notice</h2>
    <p class="auth-muted">Version {{ config('contractor_marketplace.privacy_version') }}. Applies to work on properties in England.</p>

    <h3 class="auth-h3">Marketplace</h3>
    <p>If a landlord invites you, only that landlord can send you jobs. To be shown to other landlords you accept the marketplace terms. Resisquare then keeps the commission rate shown when you accept, on each marketplace job you are awarded. A later change to the rate applies to new acceptances. Work from a landlord who invited you stays between you and that landlord.</p>

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
