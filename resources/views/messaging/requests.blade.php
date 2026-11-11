@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $statusText = [
        'pending' => 'Waiting for a reply',
        'declined' => 'Not accepted',
        'blocked' => 'Not accepted',
        'expired' => 'No reply',
        'withdrawn' => 'Withdrawn',
    ];
@endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')

    <h2 class="h6 mt-3">Requests to you</h2>
    @forelse($incoming as $req)
        @php $from = $req->fromParty(); @endphp
        <div class="msg-card" data-request="{{ $req->public_id }}">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <strong>{{ $from->displayName() }}</strong>
                    <span class="msg-muted">· {{ $from->roleLabel() }}</span>
                    <div class="msg-muted">{{ $req->updated_at->diffForHumans() }}</div>
                </div>
                <div class="msg-actions">
                    <form method="POST" action="{{ $r('requests.accept', $req) }}">@csrf<button class="btn btn-sm lw-btn-primary" type="submit">Accept</button></form>
                    <form method="POST" action="{{ $r('requests.decline', $req) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Decline</button></form>
                    <form method="POST" action="{{ $r('requests.block', $req) }}" onsubmit="return confirm('Block this sender? They will not be able to contact you again.')">@csrf<button class="btn btn-sm btn-outline-danger" type="submit">Block</button></form>
                </div>
            </div>
            @if(filled($req->intro))
                <p class="mb-1 mt-2">{!! nl2br(e($req->intro)) !!}</p>
                @if($req->intro_masked)<div class="msg-muted">Contact details in this note are hidden.</div>@endif
            @endif
            <details class="mt-2">
                <summary class="msg-muted">Report this request</summary>
                <form method="POST" action="{{ $r('requests.report', $req) }}" class="mt-2" style="max-width:420px">
                    @csrf
                    <select class="form-select form-select-sm mb-2" name="category" required aria-label="Reason">
                        <option value="spam">Spam</option>
                        <option value="harassment">Harassment or abuse</option>
                        <option value="scam">Scam or fraud</option>
                        <option value="other">Something else</option>
                    </select>
                    <textarea class="form-control form-control-sm mb-2" name="note" rows="2" maxlength="1000" placeholder="Anything we should know (optional)"></textarea>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Send report</button>
                </form>
            </details>
        </div>
    @empty
        <div class="msg-card msg-muted">No requests waiting.</div>
    @endforelse

    <h2 class="h6 mt-4">Requests you sent</h2>
    @forelse($sent as $req)
        @php $to = $req->toParty(); @endphp
        <div class="msg-card d-flex justify-content-between flex-wrap gap-2">
            <div>
                {{-- Requests sent by email keep showing the address typed until accepted. --}}
                <strong>{{ $req->to_label ?: ($to?->displayName() ?? 'Unknown') }}</strong>
                <div class="msg-muted">
                    {{ $statusText[$req->status] ?? ucfirst($req->status) }} · {{ $req->updated_at->diffForHumans() }}
                    @if($req->status !== 'pending' && $req->cooldown_until?->isFuture())
                        · You can ask again from {{ $req->cooldown_until->format('j M Y') }}
                    @endif
                </div>
            </div>
            @if($req->status === 'pending')
                <form method="POST" action="{{ $r('requests.withdraw', $req) }}" onsubmit="return confirm('Withdraw this request? You will have to wait 30 days to ask again.')">
                    @csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Withdraw</button>
                </form>
            @endif
        </div>
    @empty
        <div class="msg-card msg-muted">You haven’t sent any requests.</div>
    @endforelse
</div>
@endsection
