@extends('backend.layout.app')

@section('content')
@php
    $plan = $subscription?->plan;
    $billingCycle = $subscription?->billing_cycle ?: 'monthly';
    $status = $subscription?->status;
    $currency = strtoupper($account->currency ?: ($plan?->currency ?: 'GBP'));
    $formatMinor = function ($minor) use ($currency) {
        return $currency . ' ' . number_format(((int) ($minor ?? 0)) / 100, 2);
    };
    $resourceRows = [
        'Properties' => $limitSummary['properties'] ?? null,
        'Branches' => $limitSummary['branches'] ?? null,
        'Staff' => $limitSummary['staff'] ?? null,
        'Property managers' => $limitSummary['property_managers'] ?? null,
    ];
    $canActivate = $subscription && ! $subscription->stripe_subscription_id && in_array($status, ['trialing', 'active', 'past_due'], true);
    $canBuyAddons = $subscription && in_array($status, ['trialing', 'active'], true) && $subscription->stripe_subscription_id;
@endphp

<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Billing &amp; Plan</h2>
            <div class="text-muted">{{ $account->account_name ?: 'Account #' . $account->id }}</div>
        </div>
        <div class="d-flex gap-2">
            @if($canActivate)
                <form method="POST" action="{{ route('backend.saas.subscription.checkout') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        {{ $status === 'trialing' ? 'Activate Subscription' : 'Pay / Activate Subscription' }}
                    </button>
                </form>
            @endif

            @if($subscription?->stripe_subscription_id || $account->stripe_customer_id)
                <form method="POST" action="{{ route('backend.billing.portal') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Manage Billing</button>
                </form>
            @endif
        </div>
    </div>

    @if($subscription?->status === 'trialing' && ! $subscription->stripe_subscription_id)
        <div class="alert alert-info">You are on a trial. Add payment method to continue after trial.</div>
    @elseif($subscription?->status === 'active' && $subscription->stripe_subscription_id)
        <div class="alert alert-success">Subscription active.</div>
    @elseif($subscription?->status === 'past_due')
        <div class="alert alert-warning">Payment failed. Please update payment method.</div>
    @elseif($subscription?->status === 'cancelled')
        <div class="alert alert-danger">Subscription cancelled.</div>
    @elseif(! $subscription)
        <div class="alert alert-warning">No subscription is linked to this account.</div>
    @endif

    <div class="row">
        <div class="col-lg-6 mb-4">
            <h5>Current Plan</h5>
            <table class="table table-bordered align-middle">
                <tbody>
                    <tr><th width="35%">Account</th><td>{{ $account->account_name ?: '-' }}</td></tr>
                    <tr><th>Account status</th><td>{{ ucwords(str_replace('_', ' ', $account->status ?: '-')) }}</td></tr>
                    <tr><th>Plan</th><td>{{ $plan?->name ?: '-' }}</td></tr>
                    <tr><th>Billing cycle</th><td>{{ ucwords($billingCycle) }}</td></tr>
                    <tr><th>Plan price</th><td>
                        @if($plan)
                            {{ $billingCycle === 'annual' ? $plan->formattedAnnualPrice() : $plan->formattedMonthlyPrice() }}
                        @else
                            -
                        @endif
                    </td></tr>
                    <tr><th>Subscription status</th><td>{{ $status ? ucwords(str_replace('_', ' ', $status)) : '-' }}</td></tr>
                    <tr><th>Stripe subscription ID</th><td>{{ $subscription?->stripe_subscription_id ?: '-' }}</td></tr>
                    <tr><th>Stripe price ID</th><td>{{ $subscription?->stripe_price_id ?: '-' }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="col-lg-6 mb-4">
            <h5>Trial / Period Dates</h5>
            <table class="table table-bordered align-middle">
                <tbody>
                    <tr><th width="35%">Trial started</th><td>{{ $subscription?->trial_started_at?->format('Y-m-d') ?: $account->trial_started_at?->format('Y-m-d') ?: '-' }}</td></tr>
                    <tr><th>Trial ends</th><td>{{ $subscription?->trial_ends_at?->format('Y-m-d') ?: $account->trial_ends_at?->format('Y-m-d') ?: '-' }}</td></tr>
                    <tr><th>Period start</th><td>{{ $subscription?->current_period_start?->format('Y-m-d') ?: '-' }}</td></tr>
                    <tr><th>Period end</th><td>{{ $subscription?->current_period_end?->format('Y-m-d') ?: '-' }}</td></tr>
                    <tr><th>Cancel at period end</th><td>{{ $subscription?->cancel_at_period_end ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Cancelled at</th><td>{{ $subscription?->cancelled_at?->format('Y-m-d H:i') ?: '-' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h5>Usage Summary</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Resource</th>
                        <th>Used</th>
                        <th>Limit</th>
                        <th>Remaining</th>
                    </tr>
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

    <div class="mb-4">
        <h5>Active Addons</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Addon</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Billing cycle</th>
                        <th>Price snapshot</th>
                        <th>Stripe item ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeAddons as $subscriptionAddon)
                        <tr>
                            <td>{{ $subscriptionAddon->addon_name_at_purchase ?: $subscriptionAddon->addon?->name ?: '-' }}</td>
                            <td>{{ $subscriptionAddon->addon ? ucwords(str_replace('_', ' ', $subscriptionAddon->addon->addon_type)) : '-' }}</td>
                            <td>{{ $subscriptionAddon->quantity }}</td>
                            <td>{{ ucwords($subscriptionAddon->billing_cycle) }}</td>
                            <td>{{ $subscriptionAddon->price_at_purchase_minor !== null ? $formatMinor($subscriptionAddon->price_at_purchase_minor) : '-' }}</td>
                            <td>{{ $subscriptionAddon->stripe_subscription_item_id ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No active addons.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h5>Available Addons</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Addon</th>
                        <th>Type</th>
                        <th>Grant</th>
                        <th>Price</th>
                        <th>Stackable</th>
                        <th width="220">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($availableAddons as $addon)
                        <tr>
                            <td>{{ $addon->name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $addon->addon_type)) }}</td>
                            <td>{{ $addon->grant_quantity }}</td>
                            <td>{{ $billingCycle === 'annual' ? $addon->formattedAnnualPrice() : $addon->formattedMonthlyPrice() }}</td>
                            <td>{{ $addon->is_stackable ? 'Yes' : 'No' }}</td>
                            <td>
                                @if($canBuyAddons)
                                    <form method="POST" action="{{ route('backend.billing.addons.checkout', $addon) }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="number" name="quantity" min="1" value="1" class="form-control form-control-sm" style="max-width: 80px;">
                                        <button type="submit" class="btn btn-sm btn-primary">Buy Addon</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>Activate subscription first</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No addons available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
