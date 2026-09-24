@extends('backend.layout.app')

@section('content')
@php
    $plan = $subscription?->plan;
    $billingCycle = $subscription?->billing_cycle ?: 'monthly';
    $status = $subscription?->status;
    $currency = strtoupper($account->currency ?: ($plan?->currency ?: 'GBP'));
    $formatMinor = function ($minor) use ($currency) {
        $symbol = $currency === 'GBP' ? '£' : ($currency.' ');
        return $symbol . number_format(((int) ($minor ?? 0)) / 100, 2);
    };
    $resourceRows = [
        'Properties' => $limitSummary['properties'] ?? null,
    ];
    if (! is_landlord_plan_user()) {
        $resourceRows = array_merge($resourceRows, [
            'Branches' => $limitSummary['branches'] ?? null,
            'Staff' => $limitSummary['staff'] ?? null,
            'Property managers' => $limitSummary['property_managers'] ?? null,
        ]);
    }
    $canActivate = $subscription && ! $subscription->stripe_subscription_id && in_array($status, ['trialing', 'active', 'past_due'], true);
    $canBuyAddons = $subscription && in_array($status, ['trialing', 'active'], true) && $subscription->stripe_subscription_id;
    $statusTone = match ($status) {
        'active' => 'ok',
        'trialing' => 'warn',
        'past_due', 'cancelled' => 'bad',
        default => 'idle',
    };
    $formatDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
@endphp

<div class="container-fluid lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>{{ is_landlord_plan_user() ? 'Settings' : 'Billing & Plan' }}</h4>
            <p>{{ $account->account_name ?: 'Your workspace plan' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if($status)
                <x-lw.pill :tone="$statusTone">{{ ucwords(str_replace('_', ' ', $status)) }}</x-lw.pill>
            @endif
            @if($canActivate)
                <form method="POST" action="{{ route('backend.saas.subscription.checkout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn lw-btn-primary">
                        {{ $status === 'trialing' ? 'Activate subscription' : 'Pay / activate' }}
                    </button>
                </form>
            @endif
            @if($subscription?->stripe_subscription_id || $account->stripe_customer_id)
                <form method="POST" action="{{ route('backend.billing.portal') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn lw-btn-secondary">Manage billing</button>
                </form>
            @endif
        </div>
    </div>

    @if($subscription?->status === 'trialing' && ! $subscription->stripe_subscription_id)
        <div class="alert alert-lw mb-3">You are on a trial. Add a payment method before it ends to keep access.</div>
    @elseif($subscription?->status === 'past_due')
        <div class="alert alert-danger mb-3">Payment failed. Update your card in Manage billing.</div>
    @elseif($subscription?->status === 'cancelled')
        <div class="alert alert-danger mb-3">Subscription cancelled.</div>
    @elseif(! $subscription)
        <div class="alert alert-lw mb-3">No subscription is linked to this account yet.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Current plan</div>
                    <table class="table lw-table mb-0">
                        <tbody>
                            <tr><th width="40%">Account</th><td>{{ $account->account_name ?: '—' }}</td></tr>
                            <tr><th>Plan</th><td>{{ $plan?->name ?: '—' }}</td></tr>
                            <tr><th>Billing cycle</th><td>{{ ucwords($billingCycle) }}</td></tr>
                            <tr><th>Price</th><td>
                                @if($plan)
                                    {{ $billingCycle === 'annual' ? $plan->formattedAnnualPrice() : $plan->formattedMonthlyPrice() }}
                                @else
                                    —
                                @endif
                            </td></tr>
                            <tr><th>Status</th><td>{{ $status ? ucwords(str_replace('_', ' ', $status)) : '—' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <div class="lw-section-title mt-0">Dates</div>
                    <table class="table lw-table mb-0">
                        <tbody>
                            <tr><th width="40%">Trial started</th><td>{{ $formatDate($subscription?->trial_started_at ?: $account->trial_started_at) }}</td></tr>
                            <tr><th>Trial ends</th><td>{{ $formatDate($subscription?->trial_ends_at ?: $account->trial_ends_at) }}</td></tr>
                            <tr><th>Period start</th><td>{{ $formatDate($subscription?->current_period_start) }}</td></tr>
                            <tr><th>Period end</th><td>{{ $formatDate($subscription?->current_period_end) }}</td></tr>
                            <tr><th>Cancel at period end</th><td>{{ $subscription?->cancel_at_period_end ? 'Yes' : 'No' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Usage</div>
            <table class="table lw-table align-middle mb-0">
                <thead>
                    <tr><th>Resource</th><th>Used</th><th>Limit</th><th>Remaining</th></tr>
                </thead>
                <tbody>
                    @foreach($resourceRows as $label => $summary)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $summary['used'] ?? 0 }}</td>
                            <td>{{ $summary['limit'] ?? 0 }}</td>
                            <td>{{ $summary['remaining'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @unless(is_landlord_plan_user())
    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Active addons</div>
            <table class="table lw-table align-middle mb-0">
                <thead>
                    <tr><th>Addon</th><th>Type</th><th>Qty</th><th>Cycle</th><th>Price</th></tr>
                </thead>
                <tbody>
                    @forelse($activeAddons as $subscriptionAddon)
                        <tr>
                            <td>{{ $subscriptionAddon->addon_name_at_purchase ?: $subscriptionAddon->addon?->name ?: '—' }}</td>
                            <td>{{ $subscriptionAddon->addon ? ucwords(str_replace('_', ' ', $subscriptionAddon->addon->addon_type)) : '—' }}</td>
                            <td>{{ $subscriptionAddon->quantity }}</td>
                            <td>{{ ucwords($subscriptionAddon->billing_cycle) }}</td>
                            <td>{{ $subscriptionAddon->price_at_purchase_minor !== null ? $formatMinor($subscriptionAddon->price_at_purchase_minor) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No active addons.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Available addons</div>
            <table class="table lw-table align-middle mb-0">
                <thead>
                    <tr><th>Addon</th><th>Type</th><th>Grant</th><th>Price</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($availableAddons as $addon)
                        <tr>
                            <td>{{ $addon->name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $addon->addon_type)) }}</td>
                            <td>{{ $addon->grant_quantity }}</td>
                            <td>{{ $billingCycle === 'annual' ? $addon->formattedAnnualPrice() : $addon->formattedMonthlyPrice() }}</td>
                            <td>
                                @if($canBuyAddons)
                                    <form method="POST" action="{{ route('backend.billing.addons.checkout', $addon) }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="number" name="quantity" min="1" value="1" class="form-control form-control-sm" style="max-width: 80px;">
                                        <button type="submit" class="btn lw-btn-primary btn-sm">Buy</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Activate subscription first</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No addons available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endunless
</div>
@endsection
