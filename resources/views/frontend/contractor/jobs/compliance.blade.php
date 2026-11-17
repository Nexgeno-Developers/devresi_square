@extends('backend.layout.app')

@section('content')
@php
    $booking = $offer->booking;
    $check = $booking?->type?->name ?: 'Compliance check';
@endphp
<div class="container-fluid py-3" style="max-width: 760px;">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

    <p class="contractor-welcome-kicker" style="letter-spacing:.12em;text-transform:uppercase;font-weight:700;color:#ff5c1d;">Compliance check</p>
    <h1 class="h3">{{ $check }}</h1>
    <p class="text-muted">{{ $offer->placeLine() }}</p>
    <p><span class="badge bg-light text-dark border">{{ $offer->statusLabel() }}</span></p>

    @if($offer->showsAddress() && $booking?->access_notes)
        <div class="alert alert-light border">{{ $booking->access_notes }}</div>
    @endif

    @if($offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED)
        <div class="card">
            <div class="card-body d-flex flex-column gap-3">
                <p class="mb-0">The landlord accepted your quote.</p>
                <p class="mb-0">{{ $offer->priceSummary() }}@if($offer->scheduled_for) · visit {{ $offer->scheduled_for->format('D j M, H:i') }}@endif</p>
                @if($offer->finalGross() > (float) $offer->gross_amount)
                    <p class="mb-0">With approved extra work: <strong>£{{ number_format($offer->finalGross(), 2) }}</strong></p>
                @endif
                @if($offer->disputed_at)
                    <p class="mb-0 text-danger">Disputed: {{ $offer->dispute_reason }}</p>
                @endif
                @if($offer->rating)
                    <p class="mb-0">The landlord rated this check {{ $offer->rating }} out of 5.@if($offer->rating_comment) “{{ $offer->rating_comment }}”@endif</p>
                @endif
                @foreach($offer->variations as $variation)
                    <div class="small border rounded p-2">
                        Extra work £{{ number_format((float) $variation->gross_amount, 2) }} · {{ $variation->statusLabel() }}
                        <div>{{ $variation->reason }}</div>
                        @if($variation->status === \App\Models\ComplianceJobVariation::PENDING)
                            <form method="POST" action="{{ route('contractor.jobs.compliance.variation.withdraw', [$offer, $variation]) }}" class="mt-1">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm" type="submit">Withdraw</button>
                            </form>
                        @endif
                    </div>
                @endforeach
                @if($offer->reschedule_slots)
                    <div class="border rounded p-2">
                        <div class="small mb-1">The landlord offered a new visit time.@if($offer->reschedule_note) {{ $offer->reschedule_note }}@endif</div>
                        @foreach($offer->reschedule_slots as $slot)
                            <form method="POST" action="{{ route('contractor.jobs.compliance.reschedule', $offer) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="slot" value="{{ $slot }}">
                                <button class="btn btn-outline-primary btn-sm mb-1" type="submit">Accept {{ \Illuminate\Support\Carbon::parse($slot)->format('D j M, H:i') }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
                @if($offer->completion_note)
                    <p class="mb-0">{{ $offer->completion_note }}</p>
                @endif
                @if($offer->certificate)
                    <p class="mb-0">Certificate issued {{ $offer->certificate->issued_date?->format('j M Y') }}, expires {{ $offer->certificate->expiry_date?->format('j M Y') }}.</p>
                @endif
                @if($offer->job_status === \App\Models\ComplianceJobOffer::JOB_INVOICED)
                    <p class="mb-0 text-success">The landlord confirmed this check is complete.</p>
                    <p class="mb-0">Invoice {{ $offer->invoice?->number }} for £{{ number_format((float) $offer->invoice?->gross_amount, 2) }}.</p>
                    <a href="{{ route('contractor.jobs.compliance.invoice', $offer) }}">View invoice</a>
                @elseif($offer->job_status === \App\Models\ComplianceJobOffer::JOB_CONFIRMED)
                    <p class="mb-0 text-success">The landlord confirmed this check is complete.</p>
                    @if($offer->variations->contains('status', \App\Models\ComplianceJobVariation::PENDING))
                        <p class="mb-0">Waiting for the landlord to answer your extra work request.</p>
                    @elseif(! $offer->contractor?->contractorProfile?->hasBankDetails())
                        <p class="mb-0">Add the account Resisquare pays after the landlord pays this invoice.</p>
                        <a class="btn btn-primary btn-sm align-self-start" href="{{ route('contractor.profile.bank') }}">Add bank details</a>
                    @else
                        <form method="POST" action="{{ route('contractor.jobs.compliance.invoice.store', $offer) }}" class="d-flex flex-column gap-2">
                            @csrf
                            <p class="mb-0">Invoice total: <strong>£{{ number_format($offer->finalGross(), 2) }}</strong></p>
                            <label class="form-label small mb-0" for="invoice_notes">Message on the invoice</label>
                            <textarea class="form-control form-control-sm" id="invoice_notes" name="notes" rows="2" maxlength="2000" placeholder="Optional">{{ old('notes') }}</textarea>
                            <div><button class="btn btn-primary btn-sm" type="submit">Issue invoice</button></div>
                        </form>
                    @endif
                @elseif($offer->job_status === \App\Models\ComplianceJobOffer::JOB_COMPLETED)
                    <p class="mb-0">Marked complete {{ $offer->completed_at?->diffForHumans() }}. Waiting for the landlord to confirm.</p>
                @else
                    <form method="POST" action="{{ route('contractor.jobs.compliance.progress', $offer) }}" class="d-flex flex-wrap gap-2 align-items-end">
                        @csrf
                        <input type="hidden" name="step" value="schedule">
                        <div>
                            <label class="form-label small mb-1" for="scheduled_for">Visit</label>
                            <input class="form-control form-control-sm" id="scheduled_for" type="datetime-local" name="scheduled_for" required value="{{ old('scheduled_for', $offer->scheduled_for?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <button class="btn btn-outline-primary btn-sm" type="submit">{{ $offer->scheduled_for ? 'Change visit' : 'Book visit' }}</button>
                    </form>
                    @if($offer->job_status !== \App\Models\ComplianceJobOffer::JOB_IN_PROGRESS)
                        <form method="POST" action="{{ route('contractor.jobs.compliance.progress', $offer) }}">
                            @csrf
                            <input type="hidden" name="step" value="start">
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Start work</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('contractor.jobs.compliance.progress', $offer) }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                        @csrf
                        <input type="hidden" name="step" value="complete">
                        <label class="form-label small mb-0" for="completion_note">What did you do?</label>
                        <textarea class="form-control form-control-sm" id="completion_note" name="completion_note" rows="2" maxlength="2000" placeholder="Optional note for the landlord">{{ old('completion_note') }}</textarea>
                        <div>
                            <label class="form-label small mb-1" for="issued_date">Date on the certificate</label>
                            <input class="form-control form-control-sm" id="issued_date" type="date" name="issued_date" required value="{{ old('issued_date') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1" for="expiry_date">Expiry date</label>
                            <input class="form-control form-control-sm" id="expiry_date" type="date" name="expiry_date" required value="{{ old('expiry_date') }}">
                        </div>
                        <div>
                            <label class="form-label small mb-1" for="certificate">Certificate</label>
                            <input class="form-control form-control-sm" id="certificate" type="file" name="certificate" accept="application/pdf,image/*" required>
                        </div>
                        <div><button class="btn btn-success btn-sm" type="submit">Mark complete</button></div>
                    </form>
                @endif
                @if(! in_array($offer->job_status, [\App\Models\ComplianceJobOffer::JOB_CONFIRMED, \App\Models\ComplianceJobOffer::JOB_INVOICED], true))
                    <form method="POST" action="{{ route('contractor.jobs.compliance.variation', $offer) }}" class="d-flex flex-wrap gap-2 align-items-end">
                        @csrf
                        <div>
                            <label class="form-label small mb-1" for="extra_amount">Extra cost</label>
                            <input class="form-control form-control-sm" id="extra_amount" type="number" step="0.01" min="0.01" name="amount" required>
                        </div>
                        <div>
                            <label class="form-label small mb-1" for="extra_reason">What is it?</label>
                            <input class="form-control form-control-sm" id="extra_reason" name="reason" maxlength="1000" required>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" type="submit">Ask for extra work</button>
                    </form>
                @endif
                @if(! $offer->isFinished())
                    <form method="POST" action="{{ route('contractor.jobs.compliance.cancel', $offer) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input class="form-control form-control-sm" name="reason" maxlength="500" placeholder="Reason for cancelling" style="max-width: 240px">
                        <button class="btn btn-outline-danger btn-sm" type="submit">Cancel job</button>
                    </form>
                @endif
                @if(! $offer->disputed_at)
                    <form method="POST" action="{{ route('contractor.jobs.compliance.dispute', $offer) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input class="form-control form-control-sm" name="reason" required maxlength="2000" placeholder="What is the problem?" style="max-width: 240px">
                        <button class="btn btn-outline-warning btn-sm" type="submit">Report a problem</button>
                    </form>
                @endif
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
        @if($offer->cancel_reason)
            <p>Cancelled: {{ $offer->cancel_reason }}</p>
        @endif
    @endif

    @if($chatUrl = \App\Support\Messaging\ChatLink::to(\App\Models\Conversation::KIND_COMPLIANCE_JOB, $offer->id, 'contractor'))
        <div class="mt-3">
            @include('messaging.partials.chat-link', ['url' => $chatUrl, 'label' => $offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED ? 'Open the job chat (landlord and tenants)' : 'View the archived job chat'])
        </div>
    @endif
    @if($offer->canMessage() || $offer->messages->isNotEmpty())
        <div class="card mt-3" id="messages">
            <div class="card-body d-flex flex-column gap-2">
                <strong>Questions before the job is awarded</strong>
                @forelse($offer->messages as $message)
                    <div class="small"><strong>{{ $message->side === 'contractor' ? 'You' : 'Landlord' }}:</strong> {{ $message->body }}</div>
                @empty
                    <p class="small text-muted mb-0">No questions yet.</p>
                @endforelse
                @if($offer->canMessage())
                    <form method="POST" action="{{ route('contractor.jobs.compliance.message', $offer) }}" class="d-flex flex-column gap-2">
                        @csrf
                        <textarea class="form-control form-control-sm" name="body" rows="2" maxlength="2000" required placeholder="Ask the landlord a question about this job">{{ old('body') }}</textarea>
                        <div><button class="btn btn-outline-secondary btn-sm" type="submit">Send question</button></div>
                    </form>
                @else
                    <p class="small text-muted mb-0">Questions are closed. Once you are assigned, use the job chat.</p>
                @endif
            </div>
        </div>
    @endif

    <p class="mt-4"><a href="{{ route('contractor.jobs.index') }}">Back to jobs</a></p>
</div>
@endsection
