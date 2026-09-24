@props(['tab' => 'Homes'])

@php
    $tabs = [
        'Homes' => 'shell-phone',
        'Finance' => 'finance',
        'Repairs' => 'repairs',
        'People' => 'people',
        'Me' => 'profile',
    ];
@endphp

<div class="phone-wrap">
    <div class="phone llphone" data-llphone>
        <div class="phone-top">
            <span>Resisquare</span>
            <button type="button" class="llphone-top-alerts" data-lab-open="lab-alerts">
                Alerts
                <span class="shell-alert-count" aria-label="3 unread alerts">3</span>
            </button>
        </div>
        <div class="phone-body">{{ $slot }}</div>
        <nav class="phone-tabs llphone-tabs" aria-label="Landlord">
            @foreach($tabs as $label => $key)
                <a href="{{ route('backend.ui_lab', ['screen' => $key]) }}" class="{{ $tab === $label ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>
</div>

<x-ui-lab.overlay id="alerts" title="Alerts" type="sheet">
    <p class="lab-muted" style="margin:0 0 0.7rem;">Unread only: rent, repair, certificate.</p>
    <div class="metric"><span>Kitchen tap reported on Flat 12</span><span class="lab-muted">14 Sep</span></div>
    <div class="metric"><span>September rent unpaid · Tina</span><span class="lab-muted">Today</span></div>
    <div class="metric"><span>Gas Safe certificate missing</span><span class="lab-muted">Overdue</span></div>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="billing" title="Plan & billing" type="sheet">
    <p class="lab-label">Landlord Basic</p>
    <p style="margin:0 0 0.85rem;">Trial ends 17 Sep 2026 · £29 / month after that</p>
    <div class="metric"><span>Properties</span><strong>2 / 5</strong></div>
    <div class="usage"><i></i></div>
    <div class="metric"><span>Status</span><span class="lab-pill lab-pill-warn">Trialing</span></div>
    <p class="lab-muted">No Stripe IDs. Extra Branch / Staff stay off this plan.</p>
    <span class="lab-btn lab-btn-primary">Add payment method</span>
    <button type="button" class="lab-btn lab-btn-secondary" style="margin-top:0.55rem;" data-lab-open="lab-limit">What if I hit 5 homes?</button>
</x-ui-lab.overlay>

<x-ui-lab.overlay id="limit" title="Plan limit" type="sheet">
    <p style="margin:0 0 0.55rem;font-weight:650;">5 of 5 homes used</p>
    <p class="lab-muted" style="margin:0 0 0.85rem;">Adding another home opens this sheet — not a 403.</p>
    <div class="metric"><span>Extra Property</span><strong>£5 / mo</strong></div>
    <span class="lab-btn lab-btn-primary" style="width:100%;">Add Extra Property</span>
</x-ui-lab.overlay>

@include('backend.ui-lab.overlays.landlord')
