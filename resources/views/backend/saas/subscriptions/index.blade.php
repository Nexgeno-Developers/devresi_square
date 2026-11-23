@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">SaaS Subscriptions</h2>
    </div>

    <form method="GET" action="{{ route('backend.saas.subscriptions.index') }}" class="row g-2 mb-3">
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="billing_cycle" class="form-select">
                <option value="">All cycles</option>
                @foreach($billingCycles as $cycle)
                    <option value="{{ $cycle }}" @selected(($filters['billing_cycle'] ?? '') === $cycle)>
                        {{ ucwords($cycle) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="plan_id" class="form-select">
                <option value="">All plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>
                        {{ $plan->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="account_type" class="form-select">
                <option value="">All account types</option>
                @foreach($accountTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['account_type'] ?? '') === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Search account or Stripe ID">
        </div>

        <div class="col-12 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('backend.saas.subscriptions.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="rs-stack-table table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account</th>
                    <th>Plan</th>
                    <th>Billing cycle</th>
                    <th>Status</th>
                    <th>Trial ends at</th>
                    <th>Current period start</th>
                    <th>Current period end</th>
                    <th>Stripe subscription ID</th>
                    <th>Cancel at period end</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td data-label="#">{{ $subscriptions->firstItem() + $loop->index }}</td>
                        <td class="rs-stack-title" data-label="Account">
                            {{ $subscription->account?->account_name ?: '-' }}<br>
                            <small class="text-muted">{{ $subscription->account?->owner?->email }}</small>
                        </td>
                        <td data-label="Plan">{{ $subscription->plan?->name ?: '-' }}</td>
                        <td data-label="Billing cycle">{{ ucwords($subscription->billing_cycle) }}</td>
                        <td data-label="Status">{{ ucwords(str_replace('_', ' ', $subscription->status)) }}</td>
                        <td data-label="Trial ends at">{{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('Y-m-d') : '-' }}</td>
                        <td data-label="Current period start">{{ $subscription->current_period_start ? $subscription->current_period_start->format('Y-m-d') : '-' }}</td>
                        <td data-label="Current period end">{{ $subscription->current_period_end ? $subscription->current_period_end->format('Y-m-d') : '-' }}</td>
                        <td data-label="Stripe subscription ID">{{ $subscription->stripe_subscription_id ?: '-' }}</td>
                        <td data-label="Cancel at period end">{{ $subscription->cancel_at_period_end ? 'Yes' : 'No' }}</td>
                        <td class="rs-stack-actions text-center">
                            <a href="{{ route('backend.saas.subscriptions.show', $subscription) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No subscriptions found.</td>
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
