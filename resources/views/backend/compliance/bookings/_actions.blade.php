@php
    $accepted = $offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED;
    $open = $accepted && ! $offer->isFinished();
@endphp
<div id="offer-{{ $offer->id }}" class="d-flex flex-column gap-2">
    @if($offer->disputed_at)
        <div class="small text-danger">Disputed: {{ $offer->dispute_reason }}</div>
    @endif
    @if($offer->cancel_reason)
        <div class="small text-muted">Cancelled: {{ $offer->cancel_reason }}</div>
    @endif
    @if($offer->rating)
        <div class="small">Rated {{ $offer->rating }} out of 5.@if($offer->rating_comment) {{ $offer->rating_comment }}@endif</div>
    @endif
    @foreach($offer->variations as $variation)
        <div class="small border rounded p-2">
            Extra work £{{ number_format((float) $variation->gross_amount, 2) }} · {{ $variation->statusLabel() }}
            <div>{{ $variation->reason }}</div>
            @if($variation->status === \App\Models\ComplianceJobVariation::PENDING)
                <form method="POST" action="{{ route('admin.compliance.bookings.variation', [$booking, $offer, $variation]) }}" class="d-flex flex-wrap gap-2 mt-1">
                    @csrf
                    <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="Optional note" style="max-width: 220px">
                    <button class="btn btn-sm btn-success" name="decision" value="approve" type="submit">Approve</button>
                    <button class="btn btn-sm btn-outline-danger" name="decision" value="decline" type="submit">Decline</button>
                </form>
            @endif
        </div>
    @endforeach
    @if($offer->reschedule_slots)
        <div class="small">You asked to move the visit.
            @foreach($offer->reschedule_slots as $slot)
                {{ \Illuminate\Support\Carbon::parse($slot)->format('D j M, H:i') }}@if(! $loop->last), @endif
            @endforeach
            @if($offer->reschedule_note) · {{ $offer->reschedule_note }}@endif
        </div>
    @endif
    @if($open)
        <form method="POST" action="{{ route('admin.compliance.bookings.reschedule', [$booking, $offer]) }}" class="d-flex flex-column gap-1">
            @csrf
            <label class="form-label small mb-0">Ask for a new visit time</label>
            <input class="form-control form-control-sm" type="datetime-local" name="slots[]">
            <input class="form-control form-control-sm" type="datetime-local" name="slots[]">
            <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="Optional note">
            <div><button class="btn btn-sm btn-outline-primary" type="submit">Send new times</button></div>
        </form>
        <form method="POST" action="{{ route('admin.compliance.bookings.cancel', [$booking, $offer]) }}" class="d-flex flex-wrap gap-2">
            @csrf
            <input class="form-control form-control-sm" name="reason" maxlength="500" placeholder="Reason for cancelling" style="max-width: 240px">
            <button class="btn btn-sm btn-outline-danger" type="submit">Cancel booking</button>
        </form>
    @endif
    @if($accepted && ! $offer->disputed_at)
        <form method="POST" action="{{ route('admin.compliance.bookings.dispute', [$booking, $offer]) }}" class="d-flex flex-wrap gap-2">
            @csrf
            <input class="form-control form-control-sm" name="reason" required maxlength="2000" placeholder="What is the problem?" style="max-width: 240px">
            <button class="btn btn-sm btn-outline-warning" type="submit">Report a problem</button>
        </form>
    @endif
    @if($accepted && in_array($offer->job_status, [\App\Models\ComplianceJobOffer::JOB_CONFIRMED, \App\Models\ComplianceJobOffer::JOB_INVOICED], true) && ! $offer->rated_at)
        <form method="POST" action="{{ route('admin.compliance.bookings.rate', [$booking, $offer]) }}" class="d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <label class="small mb-0" for="rating-{{ $offer->id }}">Rate</label>
            <select class="form-select form-select-sm" id="rating-{{ $offer->id }}" name="rating" required style="max-width: 90px">
                @for($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}">{{ $star }}</option>
                @endfor
            </select>
            <input class="form-control form-control-sm" name="rating_comment" maxlength="1000" placeholder="Optional comment" style="max-width: 220px">
            <button class="btn btn-sm btn-outline-primary" type="submit">Save rating</button>
        </form>
    @endif
    @if($chatUrl = \App\Support\Messaging\ChatLink::to(\App\Models\Conversation::KIND_COMPLIANCE_JOB, $offer->id, 'admin'))
        <div>
            @include('messaging.partials.chat-link', ['url' => $chatUrl, 'label' => $offer->status === \App\Models\ComplianceJobOffer::JOB_ACCEPTED ? 'Open the job chat (you, contractor, tenants)' : 'View the archived job chat'])
        </div>
    @endif
    @if($offer->canMessage() || $offer->messages->isNotEmpty())
        <div>
            <div class="small text-muted">Questions before the job is awarded</div>
            @foreach($offer->messages as $message)
                <div class="small"><strong>{{ $message->side === 'landlord' ? 'You' : $offer->contractorName() }}:</strong> {{ $message->body }}</div>
            @endforeach
            @if($offer->canMessage())
                <form method="POST" action="{{ route('admin.compliance.bookings.message', [$booking, $offer]) }}" class="d-flex flex-column gap-1 mt-1">
                    @csrf
                    <textarea class="form-control form-control-sm" name="body" rows="2" maxlength="2000" required placeholder="Reply to the contractor's question"></textarea>
                    <div><button class="btn btn-sm btn-outline-secondary" type="submit">Send reply</button></div>
                </form>
            @endif
        </div>
    @endif
</div>
