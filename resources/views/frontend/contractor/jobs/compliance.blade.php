@extends('backend.layout.app')

@section('content')
@php
    $booking = $offer->booking;
    $check = $booking?->type?->name ?: 'Compliance check';
@endphp
<div class="container-fluid py-3" style="max-width: 760px;">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <p class="contractor-welcome-kicker" style="letter-spacing:.12em;text-transform:uppercase;font-weight:700;color:#ff5c1d;">Compliance check</p>
    <h1 class="h3">{{ $check }}</h1>
    <p class="text-muted">{{ $offer->placeLine() }}</p>
    <p><span class="badge bg-light text-dark border">{{ $offer->statusLabel() }}</span></p>

    @if($offer->showsAddress() && $booking?->access_notes)
        <div class="alert alert-light border">{{ $booking->access_notes }}</div>
    @endif

    @if($offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED)
        <div class="card">
            <div class="card-body">
                <p class="mb-1">The landlord accepted your quote.</p>
                <p class="mb-0">{{ $offer->priceSummary() }}@if($offer->scheduled_for) · visit {{ $offer->scheduled_for->format('D j M, H:i') }}@endif</p>
            </div>
        </div>
    @elseif($offer->canQuote())
        <form method="POST" action="{{ route('contractor.jobs.compliance.quote', $offer) }}" class="card">
            @csrf
            <div class="card-body d-flex flex-column gap-3">
                <input type="hidden" name="pricing_type" value="fixed">
                <input type="hidden" name="materials" value="included">
                <div>
                    <label class="form-label" for="amount">Your price</label>
                    <input class="form-control" id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                </div>
                <div>
                    <label class="form-label" for="visit_at">Visit time</label>
                    <input class="form-control" id="visit_at" type="datetime-local" name="visit_slots[]" value="{{ old('visit_slots.0') }}" required>
                </div>
                <div>
                    <label class="form-label" for="offer_note">Note</label>
                    <textarea class="form-control" id="offer_note" name="offer_note" rows="3" maxlength="2000">{{ old('offer_note') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Send quote</button>
            </div>
        </form>
        <form method="POST" action="{{ route('contractor.jobs.compliance.decline', $offer) }}" class="mt-3 d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <span class="small text-muted">Decline:</span>
            @foreach(config('contractor_marketplace.decline_reasons') as $code => $label)
                @continue($code === 'other')
                <button class="btn btn-outline-secondary btn-sm" name="decline_code" value="{{ $code }}" type="submit">{{ $label }}</button>
            @endforeach
        </form>
    @else
        <p class="text-muted">This invitation is closed.</p>
    @endif

    <p class="mt-4"><a href="{{ route('contractor.jobs.index') }}">Back to jobs</a></p>
</div>
@endsection
