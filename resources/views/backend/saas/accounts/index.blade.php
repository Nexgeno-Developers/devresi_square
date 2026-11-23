@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
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

        <div class="col-12 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('backend.saas.accounts.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle rs-stack-table">
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
                    @php
                        $subscription = $account->currentSubscription ?? $account->latestSubscription;
                        $accountLabel = trim((string) $account->account_name);
                        if ($accountLabel === '') {
                            $accountLabel = trim((string) ($account->owner?->name ?: $account->billing_email ?: ('Account '.$account->id)));
                        }
                        $ownerName = trim((string) ($account->owner?->name ?: ''));
                        if ($ownerName === '') {
                            $ownerName = $account->owner?->email ?: '—';
                        }
                    @endphp
                    <tr>
                        <td data-label="#">{{ $accounts->firstItem() + $loop->index }}</td>
                        <td data-label="Account name" class="rs-stack-title">{{ $accountLabel }}</td>
                        <td data-label="Account type">{{ ucwords(str_replace('_', ' ', $account->account_type)) }}</td>
                        <td data-label="Owner user">
                            {{ $ownerName }}<br>
                            <small class="text-muted">{{ $account->owner?->email }}</small>
                        </td>
                        <td data-label="Billing email">{{ $account->billing_email ?: '-' }}</td>
                        <td data-label="Status">
                            <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $account->status)) }}</span>
                        </td>
                        <td data-label="Current plan">{{ $subscription?->plan?->name ?: '-' }}</td>
                        <td data-label="Subscription status">{{ $subscription ? ucwords(str_replace('_', ' ', $subscription->status)) : '-' }}</td>
                        <td data-label="Trial ends at">{{ $account->trial_ends_at ? rs_date($account->trial_ends_at) : '—' }}</td>
                        <td data-label="Created">{{ $account->created_at ? rs_date($account->created_at) : '—' }}</td>
                        <td class="text-center rs-stack-actions">
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
