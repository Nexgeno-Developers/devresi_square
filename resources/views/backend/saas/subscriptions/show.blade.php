@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Subscription #{{ $subscription->id }}</h2>
        <a href="{{ route('backend.saas.subscriptions.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if($subscription->status === 'trialing')
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Extend trial</h5>
                <p class="text-muted">Adds days to this customer only. Existing Stripe subscriptions are updated first.</p>
                <form method="POST" action="{{ route('backend.saas.subscriptions.extend-trial', $subscription) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-sm-4 col-md-3">
                        <label for="additional_days" class="form-label">Additional days</label>
                        <input type="number" min="1" max="730" name="additional_days" id="additional_days" class="form-control" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Extend Trial</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6 mb-4">
            <h5>Subscription info</h5>
            <table class="table table-bordered">
                <tr><th width="35%">Billing cycle</th><td>{{ ucwords($subscription->billing_cycle) }}</td></tr>
                <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $subscription->status)) }}</td></tr>
                <tr><th>Plan at signup</th><td>{{ $subscription->plan_name_at_signup ?: '-' }}</td></tr>
                <tr><th>Price at signup</th><td>{{ $subscription->price_at_signup_minor !== null ? $subscription->formattedSignupPrice() : '-' }}</td></tr>
                <tr><th>Trial period</th><td>{{ $subscription->trial_started_at ? $subscription->trial_started_at->format('Y-m-d') : '-' }} to {{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('Y-m-d') : '-' }}</td></tr>
                <tr><th>Current period</th><td>{{ $subscription->current_period_start ? $subscription->current_period_start->format('Y-m-d') : '-' }} to {{ $subscription->current_period_end ? $subscription->current_period_end->format('Y-m-d') : '-' }}</td></tr>
                <tr><th>Stripe subscription ID</th><td>{{ $subscription->stripe_subscription_id ?: '-' }}</td></tr>
                <tr><th>Stripe price ID</th><td>{{ $subscription->stripe_price_id ?: '-' }}</td></tr>
                <tr><th>Cancel at period end</th><td>{{ $subscription->cancel_at_period_end ? 'Yes' : 'No' }}</td></tr>
                <tr><th>Cancelled at</th><td>{{ $subscription->cancelled_at ? $subscription->cancelled_at->format('Y-m-d H:i') : '-' }}</td></tr>
                <tr><th>Created</th><td>{{ $subscription->created_at ? $subscription->created_at->format('Y-m-d H:i') : '-' }}</td></tr>
            </table>
        </div>

        <div class="col-lg-6 mb-4">
            <h5>Account info</h5>
            <table class="table table-bordered">
                <tr><th width="35%">Account</th><td>{{ $subscription->account?->account_name ?: '-' }}</td></tr>
                <tr><th>Account type</th><td>{{ $subscription->account ? ucwords(str_replace('_', ' ', $subscription->account->account_type)) : '-' }}</td></tr>
                <tr><th>Owner</th><td>{{ $subscription->account?->owner?->name ?: '-' }}</td></tr>
                <tr><th>Owner email</th><td>{{ $subscription->account?->owner?->email ?: '-' }}</td></tr>
                <tr><th>Billing email</th><td>{{ $subscription->account?->billing_email ?: '-' }}</td></tr>
                <tr><th>Account status</th><td>{{ $subscription->account ? ucwords(str_replace('_', ' ', $subscription->account->status)) : '-' }}</td></tr>
            </table>

            @if($subscription->account)
                <a href="{{ route('backend.saas.accounts.show', $subscription->account) }}" class="btn btn-sm btn-primary">View account</a>
            @endif
        </div>
    </div>

    <div class="mb-4">
        <h5>Plan info</h5>
        @if($subscription->plan)
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th width="20%">Name</th><td>{{ $subscription->plan->name }}</td></tr>
                    <tr><th>Code</th><td><code>{{ $subscription->plan->code }}</code></td></tr>
                    <tr><th>Target type</th><td>{{ ucwords(str_replace('_', ' ', $subscription->plan->target_account_type)) }}</td></tr>
                    <tr><th>Monthly price</th><td>{{ $subscription->plan->formattedMonthlyPrice() }}</td></tr>
                    <tr><th>Annual price</th><td>{{ $subscription->plan->formattedAnnualPrice() }}</td></tr>
                    <tr><th>Trial days</th><td>{{ $subscription->plan->trial_days }}</td></tr>
                    <tr><th>Limits</th><td>Properties: {{ $subscription->plan->property_limit }}, Branches: {{ $subscription->plan->branch_limit }}, Staff: {{ $subscription->plan->staff_limit }}, Property managers: {{ $subscription->plan->property_manager_limit }}</td></tr>
                </table>
            </div>
        @else
            <div class="alert alert-secondary">No plan linked.</div>
        @endif
    </div>

    <div class="mb-4">
        <h5>Addons linked to subscription</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Addon</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Grant quantity</th>
                        <th>Billing cycle</th>
                        <th>Status</th>
                        <th>Stripe item ID</th>
                        <th>Stripe price ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscription->accountSubscriptionAddons as $subscriptionAddon)
                        <tr>
                            <td>{{ $subscriptionAddon->addon?->name ?: '-' }}</td>
                            <td>{{ $subscriptionAddon->addon ? ucwords(str_replace('_', ' ', $subscriptionAddon->addon->addon_type)) : '-' }}</td>
                            <td>{{ $subscriptionAddon->quantity }}</td>
                            <td>{{ $subscriptionAddon->addon?->grant_quantity ?: '-' }}</td>
                            <td>{{ ucwords($subscriptionAddon->billing_cycle) }}</td>
                            <td>{{ ucwords($subscriptionAddon->status) }}</td>
                            <td>{{ $subscriptionAddon->stripe_subscription_item_id ?: '-' }}</td>
                            <td>{{ $subscriptionAddon->stripe_price_id ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No addons linked.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
