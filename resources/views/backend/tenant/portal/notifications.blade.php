@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal" data-tenant-notifications="1">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Notifications</p>
            <h1>Notices</h1>
            <p>Recent messages about rent, repairs, documents and visits, then the alerts you want by email.</p>
        </div>
        <a class="tp-btn tp-btn-ghost" href="{{ route('tenant.profile') }}">Back to profile</a>
    </div>

    <div class="tp-card mb-3" data-tenant-notices="list">
        <h2>Recent notices</h2>
        @forelse($notices as $notice)
            <div class="tp-row" data-notice="{{ $notice->id }}">
                <div>
                    <p class="mb-0">{{ $notice->subject }}</p>
                    <div class="tp-muted mb-0">{!! safe_html($notice->message) !!}</div>
                </div>
                @if(! empty($notice->payload['action_url']))
                    <a class="tp-btn tp-btn-ghost" href="{{ $notice->payload['action_url'] }}">Open</a>
                @endif
            </div>
        @empty
            <p class="tp-empty mb-0">No notices yet.</p>
        @endforelse
    </div>

    <form class="tp-card" method="POST" action="{{ route('tenant.notifications.update') }}">
        @csrf
        @method('PUT')
        <div class="tp-stack">
            @forelse($definitions as $eventKey => $definition)
                @php
                    $pref = $userPreferences->get($eventKey);
                    $locked = collect($definition['locked_channels'] ?? []);
                    $emailOn = $locked->contains('email') || ($pref ? $pref->email_enabled : in_array('email', $definition['channels'] ?? [], true));
                    $inAppOn = $locked->contains('system') || ($pref ? $pref->in_app_enabled : in_array('system', $definition['channels'] ?? [], true));
                @endphp
                <div class="tp-row tp-pref-row">
                    <div>
                        <input type="hidden" name="preferences[{{ $loop->index }}][event_key]" value="{{ $eventKey }}">
                        <p class="mb-0 fw-semibold">{{ str($eventKey)->replace(['.', '_'], ' ')->title() }}</p>
                        <p class="tp-muted mb-0">{{ ucfirst($definition['category'] ?? 'general') }}</p>
                    </div>
                    <div class="tp-pref-toggles">
                        <label class="tp-check">
                            <input type="hidden" name="preferences[{{ $loop->index }}][email_enabled]" value="0">
                            <input type="checkbox" name="preferences[{{ $loop->index }}][email_enabled]" value="1" @checked($emailOn) @disabled($locked->contains('email'))>
                            Email
                        </label>
                        <label class="tp-check">
                            <input type="hidden" name="preferences[{{ $loop->index }}][in_app_enabled]" value="0">
                            <input type="checkbox" name="preferences[{{ $loop->index }}][in_app_enabled]" value="1" @checked($inAppOn) @disabled($locked->contains('system'))>
                            In-app
                        </label>
                    </div>
                </div>
            @empty
                <p class="tp-empty mb-0">No tenant notification events are configured yet.</p>
            @endforelse
        </div>
        @if($definitions->isNotEmpty())
            <button type="submit" class="tp-btn mt-3">Save preferences</button>
        @endif
    </form>
</div>
@endsection
