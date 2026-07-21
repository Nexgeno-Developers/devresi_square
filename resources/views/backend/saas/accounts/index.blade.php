@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">SaaS Accounts</h2>
    </div>

    <form method="GET" action="{{ route('backend.saas.accounts.index') }}" class="row g-2 mb-3">
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
            <select name="plan_id" class="form-select">
                <option value="">All plans</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>
                        {{ $plan->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Search account, owner, email">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('backend.saas.accounts.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account name</th>
                    <th>Account type</th>
                    <th>Owner user</th>
                    <th>Billing email</th>
                    <th>Status</th>
                    <th>Current plan</th>
                    <th>Subscription status</th>
                    <th>Trial ends at</th>
                    <th>Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    @php($subscription = $account->currentSubscription ?? $account->latestSubscription)
                    <tr>
                        <td>{{ $accounts->firstItem() + $loop->index }}</td>
                        <td>{{ $account->account_name ?: '-' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $account->account_type)) }}</td>
                        <td>
                            {{ $account->owner?->name ?: '-' }}<br>
                            <small class="text-muted">{{ $account->owner?->email }}</small>
                        </td>
                        <td>{{ $account->billing_email ?: '-' }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $account->status)) }}</span>
                        </td>
                        <td>{{ $subscription?->plan?->name ?: '-' }}</td>
                        <td>{{ $subscription ? ucwords(str_replace('_', ' ', $subscription->status)) : '-' }}</td>
                        <td>{{ $account->trial_ends_at ? $account->trial_ends_at->format('Y-m-d') : '-' }}</td>
                        <td>{{ $account->created_at ? $account->created_at->format('Y-m-d') : '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('backend.saas.accounts.show', $account) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <form action="{{ route('backend.saas.accounts.login-as', $account) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" @disabled(! $account->owner || ! $account->owner->can_login || ! $account->owner->status)>
                                    <i class="fas fa-right-to-bracket"></i> Login as account
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $accounts->links() }}
    </div>
</div>
@endsection
