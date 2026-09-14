@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">SaaS Billing</h2>
            <div class="text-muted">Platform subscription revenue. This is not rent Finance, Accounting, or customer Billing &amp; Plan.</div>
        </div>
        <a href="{{ route('backend.saas.subscriptions.index') }}" class="btn btn-outline-primary">All subscriptions</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Monthly recurring revenue</div>
                <div class="fs-4 fw-bold">£{{ number_format($metrics['mrr'], 2) }}</div>
                <div class="text-muted small">ARR £{{ number_format($metrics['arr'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Paid / past due</div>
                <div class="fs-4 fw-bold">{{ $metrics['active'] }} / {{ $metrics['past_due'] }}</div>
                <div class="text-muted small">{{ $metrics['trialing'] }} currently trialing</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Stripe subscriptions</div>
                <div class="fs-4 fw-bold">{{ $metrics['stripe_linked'] }}</div>
                <div class="text-muted small">Rows with a Stripe subscription ID</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Customer checkout</div>
                <div class="small mb-0">Landlords still use <strong>Billing &amp; Plan</strong>. Super Admin uses this tab and account actions instead of activating a customer card.</div>
            </div></div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Plan</th>
                    <th>Cycle</th>
                    <th>Status</th>
                    <th>Price at signup</th>
                    <th>Trial ends</th>
                    <th>Stripe subscription</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td>
                            {{ $subscription->account?->account_name ?: '-' }}<br>
                            <small class="text-muted">{{ $subscription->account?->billing_email ?: $subscription->account?->owner?->email }}</small>
                        </td>
                        <td>{{ $subscription->plan?->name ?: ($subscription->plan_name_at_signup ?: '-') }}</td>
                        <td>{{ ucwords($subscription->billing_cycle) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $subscription->status)) }}</td>
                        <td>{{ $subscription->formattedSignupPrice() }}</td>
                        <td>{{ $subscription->trial_ends_at?->format('Y-m-d') ?: '-' }}</td>
                        <td>{{ $subscription->stripe_subscription_id ?: '-' }}</td>
                        <td class="text-nowrap text-center">
                            @if($subscription->account)
                                <a href="{{ route('backend.saas.accounts.show', $subscription->account) }}" class="btn btn-sm btn-light">Account</a>
                            @endif
                            <a href="{{ route('backend.saas.subscriptions.show', $subscription) }}" class="btn btn-sm btn-primary">Subscription</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No SaaS subscriptions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $subscriptions->links() }}
    </div>
</div>
@endsection
