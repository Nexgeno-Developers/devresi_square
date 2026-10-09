@extends('backend.layout.app')

@section('content')
@php
    $profile = $offer->contractor?->contractorProfile;
    $inspection = $offer->pricing_type === 'inspection';
    $slot = $offer->earliestSlot();
@endphp
<div class="container-fluid lw-page" style="max-width: 720px;">
    <div class="lw-hero">
        <h4>{{ $inspection ? 'Accept inspection visit?' : 'Accept this offer?' }}</h4>
        <p>{{ $profile?->displayName() ?: 'Contractor' }}</p>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="fs-4 fw-semibold mb-1">£{{ number_format((float) $offer->gross_amount, 2) }}</div>
            <p class="mb-2">{{ $summary }}</p>
            @if($offer->revision > 0)
                <p class="small text-warning mb-2">The contractor updated this offer (version {{ $offer->revision + 1 }}).</p>
            @endif
            <ul class="small mb-2">
                <li>Visit: {{ $slot ? $slot->format('D j M, H:i') : 'to be arranged' }}</li>
                <li>Valid until {{ optional($offer->offer_expires_at)->format('D j M, H:i') }}</li>
                @if($offer->offer_note)<li>Note: {{ $offer->offer_note }}</li>@endif
            </ul>
            <div class="d-flex flex-wrap gap-1">
                @foreach($badges as $badge)<x-lw.pill tone="ok">{{ $badge }}</x-lw.pill>@endforeach
            </div>
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body small">
            <div class="lw-section-title mt-0">What happens next</div>
            @if($inspection)
                <p class="mb-1">The contractor gets the address and access notes so they can inspect. They then send a firm price for you to accept.</p>
            @else
                <p class="mb-1">The contractor gets the full address, access notes and tenant contact, plus a calendar invite.</p>
                @if($others)<p class="mb-1">The other {{ $others }} {{ \Illuminate\Support\Str::plural('contractor', $others) }} will be told they were not selected.</p>@endif
                <p class="mb-1">Your tenant is told who is coming and when.</p>
            @endif
            <p class="mb-0 text-muted">CIS deductions, if they apply, are your responsibility. This platform does not deduct or report CIS.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.property_repairs.job_offers.accept', $offer) }}" class="d-flex gap-2 mb-4">
        @csrf
        <input type="hidden" name="revision" value="{{ $offer->revision }}">
        <button class="btn btn-primary" type="submit">{{ $inspection ? 'Accept inspection' : 'Accept offer' }}</button>
        <a class="btn lw-btn-ghost" href="{{ route('admin.property_repairs.show', $offer->repair_issue_id) }}">Back</a>
    </form>
</div>
@endsection
