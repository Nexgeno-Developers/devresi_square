@extends('backend.layout.app')

@section('content')
@php
    $property = $booking->property;
    $address = trim(collect([$property?->line_1, $property?->city, $property?->postcode])->filter()->implode(', '));
@endphp
<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4>{{ $booking->type?->name }}</h4>
            <p>{{ $address ?: 'Property' }} · {{ $booking->landlordStatus() }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(in_array($booking->status, [\App\Models\ComplianceBooking::DRAFT, \App\Models\ComplianceBooking::SENT], true))
                <a href="{{ route('admin.compliance.bookings.find', $booking) }}" class="btn lw-btn-secondary">Add contractors</a>
            @endif
            <a href="{{ route('admin.compliance.index') }}" class="btn lw-btn-secondary">Compliance</a>
        </div>
    </div>

    @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $flash => $tone)
        @if(session($flash))
            <div class="alert alert-{{ $tone }}">{{ session($flash) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        @forelse($quotes as $offer)
            <div class="col-md-6 col-xl-4">
                <div class="card lw-card h-100">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>{{ $offer->contractorName() }}</strong>
                            <span class="badge bg-light text-dark border">{{ $offer->statusLabel() }}</span>
                        </div>
                        <div>{{ $offer->priceSummary() }}</div>
                        @if($offer->scheduled_for)
                            <div class="small text-muted">Visit {{ $offer->scheduled_for->format('D j M, H:i') }}</div>
                        @elseif($offer->visit_slots)
                            <div class="small text-muted">
                                @foreach($offer->visit_slots as $slot)
                                    <div>{{ \Illuminate\Support\Carbon::parse($slot)->format('D j M, H:i') }}</div>
                                @endforeach
                            </div>
                        @endif
                        @if($offer->offer_note)
                            <div class="small">{{ $offer->offer_note }}</div>
                        @endif
                        @if($offer->completion_note)
                            <div class="small">{{ $offer->completion_note }}</div>
                        @endif
                        @if($offer->certificate)
                            <div class="small">
                                Issued {{ $offer->certificate->issued_date?->format('j M Y') }}, expires {{ $offer->certificate->expiry_date?->format('j M Y') }}.
                                <a href="{{ uploaded_asset($offer->certificate->photos) }}">View certificate</a>
                            </div>
                        @endif
                        @if($offer->invoice)
                            <a href="{{ route('admin.compliance.bookings.invoice', [$booking, $offer]) }}">View invoice {{ $offer->invoice->number }}</a>
                        @endif
                        @include('backend.compliance.bookings._actions')
                        @if($offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED && $offer->job_status === \App\Models\ComplianceJobOffer::JOB_COMPLETED)
                            <form method="POST" action="{{ route('admin.compliance.bookings.confirm', [$booking, $offer]) }}" class="mt-auto">
                                @csrf
                                <button class="btn btn-sm btn-success" type="submit">Confirm complete</button>
                            </form>
                        @endif
                        @if($offer->status === \App\Models\ComplianceJobOffer::OFFER_SENT)
                            <form method="POST" action="{{ route('admin.compliance.bookings.accept', [$booking, $offer]) }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="revision" value="{{ $offer->revision }}">
                                <button class="btn btn-sm lw-btn-primary" type="submit">Accept quote</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card lw-card">
                    <div class="card-body text-muted mb-0">No quotes yet.</div>
                </div>
            </div>
        @endforelse
    </div>

    @if($waiting->isNotEmpty())
        <div class="card lw-card mt-3">
            <div class="card-body">
                <h5>Waiting for a quote</h5>
                <ul class="mb-0">
                    @foreach($waiting as $offer)
                        <li>
                            {{ $offer->contractorName() }} · reply by {{ optional($offer->invite_expires_at)->format('D j M, H:i') ?: 'the deadline' }}
                            @include('backend.compliance.bookings._actions')
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
