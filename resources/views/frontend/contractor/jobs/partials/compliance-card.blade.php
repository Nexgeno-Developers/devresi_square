<article class="contractor-job">
    <a href="{{ route('contractor.jobs.compliance.show', $offer) }}" class="text-reset text-decoration-none d-block">
        <div class="contractor-job-top">
            <strong>{{ $offer->booking?->type?->name ?: 'Compliance check' }}</strong>
            <span class="contractor-job-status">{{ $offer->statusLabel() }}</span>
        </div>
        <div class="contractor-job-meta">
            <span class="badge text-bg-light border">Compliance</span>
            {{ $offer->placeLine() }}
            @if($offer->scheduled_for) · visit {{ $offer->scheduled_for->format('D j M, H:i') }} @endif
        </div>
        @if($offer->isAwaitingResponse() && $offer->invite_expires_at)
            <div class="small mt-1 text-muted" data-deadline="{{ $offer->invite_expires_at->toIso8601String() }}">
                Respond {{ $offer->invite_expires_at->diffForHumans() }}
            </div>
        @endif
    </a>
    @if($offer->isAwaitingResponse())
        <form method="POST" action="{{ route('contractor.jobs.compliance.decline', $offer) }}" class="d-flex flex-wrap gap-1 mt-2 align-items-center">
            @csrf
            <span class="small text-muted me-1">Decline:</span>
            @foreach($declineReasons as $code => $label)
                @continue($code === 'other')
                <button class="btn btn-outline-secondary btn-sm" name="decline_code" value="{{ $code }}" type="submit">{{ $label }}</button>
            @endforeach
            <a class="btn btn-primary btn-sm ms-auto" href="{{ route('contractor.jobs.compliance.show', $offer) }}">Make an offer</a>
        </form>
    @endif
</article>
