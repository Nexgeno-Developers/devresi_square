@extends('backend.layout.app')

@push('styles')
<style>
    .contractor-welcome {
        background: #fff;
        border: 1px solid #e6e9ef;
        border-radius: 16px;
        padding: 1.35rem 1.4rem 1.2rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.05);
    }
    .contractor-welcome-kicker {
        margin: 0 0 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #ff5c1d;
    }
    .contractor-welcome h1 {
        margin: 0 0 0.4rem;
        font-size: 1.7rem;
        letter-spacing: -0.03em;
    }
    .contractor-welcome p { margin: 0; color: #5b6573; max-width: 38rem; }
    .contractor-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.9rem; }
    .contractor-chip {
        border-radius: 999px;
        padding: 0.2rem 0.7rem;
        background: #f4f6f8;
        font-size: 0.82rem;
        font-weight: 650;
    }
    .contractor-chip.is-wait { background: #fff4ee; color: #9a3412; }
    .contractor-hold { position: relative; min-height: 220px; }
    .contractor-hold-body {
        filter: blur(5px);
        opacity: 0.72;
        pointer-events: none;
        user-select: none;
    }
    .contractor-hold-lock {
        position: absolute;
        left: 50%;
        top: 46%;
        transform: translate(-50%, -50%);
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: #0f173a;
        color: #fff;
        border-radius: 999px;
        padding: 0.55rem 0.95rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .contractor-nav-hold {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .contractor-home-head,
    .contractor-stat,
    .contractor-alert,
    .contractor-tabs,
    .contractor-job,
    .contractor-empty {
        background: #fff;
        border: 1px solid #e6e9ef;
    }
    .contractor-home-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        border-radius: 16px;
        padding: 1.25rem 1.35rem 1.15rem;
        margin-bottom: 0.85rem;
    }
    .contractor-home-head h1 {
        margin: 0 0 0.3rem;
        font-size: 1.65rem;
        letter-spacing: -0.03em;
    }
    .contractor-home-head p { margin: 0; color: #5b6573; max-width: 36rem; }
    .contractor-home-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.45rem;
    }
    .contractor-home-actions a { font-size: 0.85rem; font-weight: 650; }
    .contractor-home-status {
        border-radius: 999px;
        padding: 0.32rem 0.75rem;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .contractor-home-status.is-ready { background: #e8f7ee; color: #166534; }
    .contractor-home-status.is-paused { background: #fff4ee; color: #9a3412; }
    .contractor-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .contractor-stat {
        border-radius: 14px;
        padding: 0.95rem 1rem;
        text-decoration: none;
        color: inherit;
    }
    .contractor-stat strong {
        display: block;
        font-size: 1.55rem;
        line-height: 1;
        letter-spacing: -0.03em;
    }
    .contractor-stat span { display: block; margin-top: 0.3rem; color: #5b6573; font-size: 0.85rem; }
    .contractor-stat.is-current { border-color: #0f173a; }
    .contractor-alert {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        border-radius: 14px;
        padding: 0.95rem 1.05rem;
        margin-bottom: 0.85rem;
        background: #fff4ee;
        border-color: #ffd7c2;
    }
    .contractor-alert.is-stop { background: #fff1f1; border-color: #fecaca; }
    .contractor-alert h2 { margin: 0 0 0.2rem; font-size: 1rem; }
    .contractor-alert p { margin: 0; color: #7c3a16; font-size: 0.9rem; }
    .contractor-alert.is-stop p { color: #991b1b; }
    .contractor-alert .btn { white-space: nowrap; background: #ff5c1d; border-color: #ff5c1d; }
    .contractor-tabs {
        display: flex;
        gap: 0.25rem;
        border-radius: 12px;
        padding: 0.3rem;
        margin-bottom: 0.85rem;
        overflow-x: auto;
    }
    .contractor-tabs a {
        flex: 1 0 auto;
        text-align: center;
        text-decoration: none;
        color: #5b6573;
        border-radius: 9px;
        padding: 0.48rem 0.75rem;
        font-weight: 650;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .contractor-tabs a.is-active { background: #0f173a; color: #fff; }
    .contractor-tabs .count {
        display: inline-block;
        min-width: 1.1rem;
        margin-left: 0.25rem;
        font-size: 0.75rem;
        opacity: 0.8;
    }
    .contractor-job { border-radius: 14px; padding: 0.95rem 1.05rem; margin-bottom: 0.65rem; }
    .contractor-job-top { display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; }
    .contractor-job-top strong { font-size: 1rem; }
    .contractor-job-status {
        font-size: 0.75rem;
        font-weight: 700;
        color: #0f173a;
        background: #f4f6f8;
        border-radius: 999px;
        padding: 0.15rem 0.55rem;
        white-space: nowrap;
    }
    .contractor-job-meta { margin-top: 0.35rem; color: #5b6573; font-size: 0.85rem; }
    .contractor-empty {
        border-style: dashed;
        border-radius: 16px;
        padding: 2.4rem 1.4rem;
        text-align: center;
    }
    .contractor-empty i { color: #ff5c1d; font-size: 1.2rem; }
    .contractor-empty h2 { margin: 0.55rem 0 0.35rem; font-size: 1.15rem; }
    .contractor-empty p { margin: 0 auto; max-width: 28rem; color: #5b6573; }
    @media (max-width: 700px) {
        .contractor-home-head, .contractor-alert { flex-direction: column; align-items: stretch; }
        .contractor-home-actions { align-items: flex-start; }
        .contractor-stats { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3" style="max-width: 1080px;">
    @if($pendingReview ?? false)
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        <section class="contractor-welcome">
            <p class="contractor-welcome-kicker">{{ get_setting('website_name') ?: 'Resisquare' }}</p>
            <h1>You're registered</h1>
            <p>Your account is set up. We're checking your documents and aim to email you within {{ $sla }} working days. You can sign in any time.</p>
            <div class="contractor-chips">
                <span class="contractor-chip is-wait">Pending review</span>
                @foreach($trades as $trade)
                    <span class="contractor-chip">{{ $trade }}</span>
                @endforeach
                @if($profile->postcode)
                    <span class="contractor-chip">{{ $profile->postcode }}</span>
                @endif
            </div>
        </section>

        <div class="contractor-hold">
            <div class="contractor-hold-lock"><i class="fa-solid fa-lock"></i> Jobs unlock after approval</div>
            <div class="contractor-hold-body" aria-hidden="true">
                <h1 class="h3 mb-3">Jobs</h1>
                <ul class="nav nav-pills mb-3">
                    @foreach($tabs as $label)
                        <li class="nav-item"><span class="nav-link {{ $loop->first ? 'active' : '' }}">{{ $label }}</span></li>
                    @endforeach
                </ul>
                <div class="card mb-2"><div class="card-body"><strong>New repair requests</strong><div class="small text-muted">Offers, visits and invoices</div></div></div>
                <div class="card mb-2"><div class="card-body"><strong>Jobs you are awarded</strong><div class="small text-muted">Schedule, updates and invoices</div></div></div>
            </div>
        </div>
    @else
    @php
        $tradeLabels = collect($profile->trades ?? [])
            ->map(fn ($trade) => \App\Support\ContractorCompliance::trades()[$trade] ?? $trade)
            ->values();
        $renewIn = ($expiring && $expiring->expires_at)
            ? (int) now()->startOfDay()->diffInDays($expiring->expires_at->copy()->startOfDay(), false)
            : null;
        $coverLine = $profile->postcode
            ? 'Landlords within '.($profile->coverage_radius_miles ?: 0).' miles of '.$profile->postcode.' send repairs that match your trades.'
            : 'Landlords send repairs that match your trades.';
        $spotlight = [
            'new' => 'New',
            'offer_sent' => 'Offers out',
            'awarded' => 'In progress',
        ];
    @endphp

    <header class="contractor-home-head">
        <div>
            <p class="contractor-welcome-kicker">Jobs</p>
            <h1>{{ $profile->displayName() }}</h1>
            <p>{{ $coverLine }}</p>
            @if($tradeLabels->isNotEmpty())
                <div class="contractor-chips">
                    @foreach($tradeLabels as $trade)
                        <span class="contractor-chip">{{ $trade }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="contractor-home-actions">
            <span class="contractor-home-status {{ $paused ? 'is-paused' : 'is-ready' }}">{{ $paused ? 'Paused' : 'Ready for jobs' }}</span>
            <a href="{{ route('contractor.profile.edit') }}">Documents</a>
        </div>
    </header>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="contractor-stats">
        @foreach($spotlight as $key => $label)
            <a class="contractor-stat {{ $tab === $key ? 'is-current' : '' }}" href="{{ route('contractor.jobs.index', ['tab' => $key]) }}">
                <strong>{{ $counts[$key] }}</strong>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>

    @if($paused)
        <div class="contractor-alert is-stop">
            <div>
                <h2>New jobs are paused</h2>
                <p>{{ $paused }} Upload a renewal before landlords can send you work.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('contractor.profile.edit') }}">Upload renewal</a>
        </div>
    @elseif($renewIn !== null && $expiring->expires_at->lte(now()->addDays(30)))
        <div class="contractor-alert">
            <div>
                <h2>{{ $expiring->label() }} expires {{ $expiring->expires_at->format('j M Y') }}</h2>
                <p>
                    @if($renewIn <= 0)
                        It expires today. Upload the renewal so you are not paused.
                    @elseif($renewIn === 1)
                        1 day left. Upload the renewal so you are not paused.
                    @else
                        {{ $renewIn }} days left. Upload the renewal so you are not paused.
                    @endif
                </p>
            </div>
            <a class="btn btn-primary" href="{{ route('contractor.profile.edit') }}">Upload renewal</a>
        </div>
    @endif

    <nav class="contractor-tabs" aria-label="Job lists">
        @foreach($tabs as $key => $label)
            <a class="{{ $tab === $key ? 'is-active' : '' }}" href="{{ route('contractor.jobs.index', ['tab' => $key]) }}">
                {{ $label }}<span class="count">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    @forelse($items as $offer)
        @php
            $repair = $offer->repairIssue;
            $property = $repair?->property;
            $place = $offer->unlocksAccess()
                ? trim(collect([$property?->line_1, $property?->city, $property?->postcode])->filter()->implode(', '))
                : trim(collect([$property?->city, \App\Support\ContractorCompliance::postcodeDistrict($property?->postcode)])->filter()->implode(' '));
            $deadline = match (true) {
                $offer->isAwaitingResponse() => $offer->invite_expires_at,
                $offer->status === 'offer_sent' => $offer->offer_expires_at,
                default => null,
            };
            $urgency = $offer->request?->urgency;
        @endphp
        <article class="contractor-job">
            <a href="{{ route('contractor.jobs.show', $offer) }}" class="text-reset text-decoration-none d-block">
                <div class="contractor-job-top">
                    <strong>{{ \Illuminate\Support\Str::limit($repair?->description ?: 'Repair', 90) }}</strong>
                    <span class="contractor-job-status">{{ $offer->statusLabel() }}</span>
                </div>
                <div class="contractor-job-meta">
                    @if($urgency === 'emergency')<span class="badge text-bg-danger">Emergency</span>@elseif($urgency === 'urgent')<span class="badge text-bg-warning">Urgent</span>@endif
                    {{ $place ?: 'Area shared on the job' }}
                    @if($offer->scheduled_for) · visit {{ $offer->scheduled_for->format('D j M, H:i') }} @endif
                    @if($offer->details_changed_at) · <span class="text-warning">details changed</span> @endif
                    @if($offer->reconfirm_requested_at && $offer->status === 'offer_expired') · <span class="text-primary">landlord wants to accept, reconfirm</span> @endif
                </div>
                @if($deadline)
                    <div class="small mt-1 {{ $deadline->diffInHours(now()) < 6 ? 'text-danger' : 'text-muted' }}" data-deadline="{{ $deadline->toIso8601String() }}">
                        {{ $offer->isAwaitingResponse() ? 'Respond' : 'Offer valid' }} {{ $deadline->diffForHumans() }}
                    </div>
                @endif
            </a>
            @if($offer->isAwaitingResponse())
                <form method="POST" action="{{ route('contractor.jobs.decline', $offer) }}" class="d-flex flex-wrap gap-1 mt-2 align-items-center">
                    @csrf
                    <span class="small text-muted me-1">Decline:</span>
                    @foreach($declineReasons as $code => $label)
                        @continue($code === 'other')
                        <button class="btn btn-outline-secondary btn-sm" name="decline_code" value="{{ $code }}" type="submit">{{ $label }}</button>
                    @endforeach
                    <a class="btn btn-primary btn-sm ms-auto" href="{{ route('contractor.jobs.show', $offer) }}">Make an offer</a>
                </form>
            @endif
        </article>
    @empty
        <div class="contractor-empty">
            <i class="fa-solid fa-wrench"></i>
            <h2>
                @switch($tab)
                    @case('new') No new jobs @break
                    @case('offer_sent') No offers waiting for a decision @break
                    @case('awarded') No jobs in progress @break
                    @case('completed') Nothing completed yet @break
                    @default Nothing here
                @endswitch
            </h2>
            <p>
                @if($tab === 'new')
                    When a landlord sends a repair, it shows up here. Open it to make an offer, or decline if you cannot take it.
                @elseif($tab === 'offer_sent')
                    Offers you send to landlords wait here until they decide.
                @elseif($tab === 'awarded')
                    Jobs a landlord awards you show up here, with the visit and the invoice.
                @else
                    Finished work stays in this list.
                @endif
            </p>
        </div>
    @endforelse
    @endif
</div>
<script>
(function () {
    const fmt = ms => {
        if (ms <= 0) return 'now';
        const h = Math.floor(ms / 3600000), m = Math.floor((ms % 3600000) / 60000);
        return h >= 24 ? Math.floor(h / 24) + 'd ' + (h % 24) + 'h' : h + 'h ' + m + 'm';
    };
    const tick = () => document.querySelectorAll('[data-deadline]').forEach(el => {
        const left = new Date(el.dataset.deadline) - Date.now();
        const prefix = el.textContent.trim().split(' ')[0] === 'Respond' ? 'Respond within ' : 'Offer valid for ';
        el.textContent = left > 0 ? prefix + fmt(left) : 'Expired';
        el.classList.toggle('text-danger', left < 6 * 3600000);
    });
    tick();
    setInterval(tick, 60000);
})();
</script>
@endsection
