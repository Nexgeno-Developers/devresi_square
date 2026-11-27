@props(['account', 'subscription' => null])
@php($state = \App\Support\SubscriptionBillingState::for($account, $subscription))
<div {{ $attributes }} data-billing-state="{{ $state['key'] }}">
    {{-- Not .badge: the workspace theme paints every badge the same, and these colours carry meaning. --}}
    <span class="d-inline-block rounded px-2 py-1 small fw-semibold border bg-{{ $state['tone'] }}-subtle text-{{ $state['tone'] }}-emphasis border-{{ $state['tone'] }}-subtle">{{ $state['label'] }}</span>
    <div class="small text-muted mt-1">{{ $state['detail'] }}</div>
</div>
