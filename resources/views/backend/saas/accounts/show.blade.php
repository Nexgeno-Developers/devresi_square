@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $account->account_name ?: 'Account #' . $account->id }}</h2>
        <div class="d-flex gap-2">
            <form action="{{ route('backend.saas.accounts.login-as', $account) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" @disabled(! $account->owner || ! $account->owner->can_login || ! $account->owner->status)>
                    <i class="fas fa-right-to-bracket me-1"></i> Login as account
                </button>
            </form>
            <a href="{{ route('backend.saas.accounts.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <h5>Account info</h5>
            <table class="table table-bordered">
                <tr><th width="35%">Account name</th><td>{{ $account->account_name ?: '-' }}</td></tr>
                <tr><th>Account type</th><td>{{ ucwords(str_replace('_', ' ', $account->account_type)) }}</td></tr>
                <tr><th>Billing email</th><td>{{ $account->billing_email ?: '-' }}</td></tr>
                <tr><th>Billing phone</th><td>{{ $account->billing_phone ?: '-' }}</td></tr>
                <tr><th>Currency</th><td>{{ $account->currency ?: 'GBP' }}</td></tr>
                <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $account->status)) }}</td></tr>
                <tr><th>Trial started</th><td>{{ $account->trial_started_at ? $account->trial_started_at->format('Y-m-d H:i') : '-' }}</td></tr>
                <tr><th>Trial ends</th><td>{{ $account->trial_ends_at ? $account->trial_ends_at->format('Y-m-d H:i') : '-' }}</td></tr>
                <tr><th>Created</th><td>{{ $account->created_at ? $account->created_at->format('Y-m-d H:i') : '-' }}</td></tr>
            </table>
        </div>

        <div class="col-lg-6 mb-4">
            <h5>Owner user</h5>
            <table class="table table-bordered">
                <tr><th width="35%">Name</th><td>{{ $account->owner?->name ?: '-' }}</td></tr>
                <tr><th>Email</th><td>{{ $account->owner?->email ?: '-' }}</td></tr>
                <tr><th>Phone</th><td>{{ $account->owner?->phone ?: '-' }}</td></tr>
                <tr><th>User type</th><td>{{ $account->owner?->user_type ?: '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $account->owner?->status ?: '-' }}</td></tr>
            </table>
        </div>

        <div class="col-lg-6 mb-4">
            <h5>Company profile</h5>
            @if($account->company)
                <table class="table table-bordered">
                    <tr><th width="35%">Name</th><td>{{ $account->company->name }}</td></tr>
                    <tr><th>Company type</th><td>{{ $account->company->company_type ? ucwords(str_replace('_', ' ', $account->company->company_type)) : '-' }}</td></tr>
                    <tr><th>Status</th><td>{{ $account->company->status ? ucwords($account->company->status) : '-' }}</td></tr>
                    <tr><th>Email</th><td>{{ is_array($account->company->emails) ? implode(', ', $account->company->emails) : ($account->company->emails ?: '-') }}</td></tr>
                    <tr><th>Phone</th><td>{{ is_array($account->company->phones) ? implode(', ', $account->company->phones) : ($account->company->phones ?: '-') }}</td></tr>
                </table>
            @else
                <div class="alert alert-secondary">No company profile linked.</div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <h5>Current subscription</h5>
            @if($currentSubscription)
                <table class="table table-bordered">
                    <tr><th width="35%">Plan</th><td>{{ $currentSubscription->plan?->name ?: '-' }}</td></tr>
                    <tr><th>Plan at signup</th><td>{{ $currentSubscription->plan_name_at_signup ?: '-' }}</td></tr>
                    <tr><th>Price at signup</th><td>{{ $currentSubscription->price_at_signup_minor !== null ? $currentSubscription->formattedSignupPrice() : '-' }}</td></tr>
                    <tr><th>Billing cycle</th><td>{{ ucwords($currentSubscription->billing_cycle) }}</td></tr>
                    <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $currentSubscription->status)) }}</td></tr>
                    <tr><th>Trial period</th><td>{{ $currentSubscription->trial_started_at ? $currentSubscription->trial_started_at->format('Y-m-d') : '-' }} to {{ $currentSubscription->trial_ends_at ? $currentSubscription->trial_ends_at->format('Y-m-d') : '-' }}</td></tr>
                    <tr><th>Current period</th><td>{{ $currentSubscription->current_period_start ? $currentSubscription->current_period_start->format('Y-m-d') : '-' }} to {{ $currentSubscription->current_period_end ? $currentSubscription->current_period_end->format('Y-m-d') : '-' }}</td></tr>
                    <tr><th>Stripe subscription</th><td>{{ $currentSubscription->stripe_subscription_id ?: '-' }}</td></tr>
                    <tr><th>Cancel at period end</th><td>{{ $currentSubscription->cancel_at_period_end ? 'Yes' : 'No' }}</td></tr>
                </table>
                <a href="{{ route('backend.saas.subscriptions.show', $currentSubscription) }}" class="btn btn-sm btn-primary">View subscription</a>
            @else
                <div class="alert alert-secondary">No subscription found.</div>
            @endif
        </div>

        <div class="col-lg-6 mb-4">
            <h5>Plan details</h5>
            @if($currentSubscription?->plan)
                @php($plan = $currentSubscription->plan)
                <table class="table table-bordered">
                    <tr><th width="35%">Name</th><td>{{ $plan->name }}</td></tr>
                    <tr><th>Code</th><td><code>{{ $plan->code }}</code></td></tr>
                    <tr><th>Target type</th><td>{{ ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</td></tr>
                    <tr><th>Monthly price</th><td>{{ $plan->formattedMonthlyPrice() }}</td></tr>
                    <tr><th>Annual price</th><td>{{ $plan->formattedAnnualPrice() }}</td></tr>
                    <tr><th>Trial days</th><td>{{ $plan->trial_days }}</td></tr>
                    <tr><th>Active</th><td>{{ $plan->is_active ? 'Yes' : 'No' }}</td></tr>
                </table>
            @else
                <div class="alert alert-secondary">No plan linked.</div>
            @endif
        </div>
    </div>

    <div class="mb-4">
        <h5>Limits summary</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Limit</th>
                        <th>Base plan</th>
                        <th>Addons</th>
                        <th>Total limit</th>
                        <th>Used</th>
                        <th>Remaining</th>
                        <th>Can add</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($limitRows as $type => $row)
                        @php
                            $usedKey = [
                                'property' => 'properties',
                                'branch' => 'branches',
                                'staff' => 'staff',
                                'property_manager' => 'property_managers',
                            ][$type] ?? null;
                            $summaryRow = $limitSummary[$usedKey] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['base'] }}</td>
                            <td>{{ $row['addons'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $usedCounts[$usedKey] ?? 0 }}</td>
                            <td>{{ $summaryRow['remaining'] ?? 0 }}</td>
                            <td>{{ ($summaryRow['can_add'] ?? false) ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h5>Feature access</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <tbody>
                    <tr><th width="35%">Company profile</th><td>{{ $limitSummary['features']['company_profile'] ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Invoice branding</th><td>{{ $limitSummary['features']['invoice_branding'] ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Roles and permissions</th><td>{{ $limitSummary['features']['roles_permissions'] ? 'Yes' : 'No' }}</td></tr>
                    <tr><th>Contact login</th><td>{{ $limitSummary['features']['contact_login'] ? 'Yes' : 'No' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4">
        <h5>Purchased addons</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Grant each</th>
                        <th>Billing cycle</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeAddons as $subscriptionAddon)
                        <tr>
                            <td>{{ $subscriptionAddon->addon?->name ?: '-' }}</td>
                            <td>{{ $subscriptionAddon->addon ? ucwords(str_replace('_', ' ', $subscriptionAddon->addon->addon_type)) : '-' }}</td>
                            <td>{{ $subscriptionAddon->quantity }}</td>
                            <td>{{ $subscriptionAddon->addon?->grant_quantity ?: '-' }}</td>
                            <td>{{ ucwords($subscriptionAddon->billing_cycle) }}</td>
                            <td>{{ ucwords($subscriptionAddon->status) }}</td>
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
        <h5>Account users</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Member type</th>
                        <th>Access level</th>
                        <th>Can login</th>
                        <th>Branch</th>
                        <th>Designation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account->accountUsers as $accountUser)
                        <tr>
                            <td>
                                {{ $accountUser->user?->name ?: '-' }}<br>
                                <small class="text-muted">{{ $accountUser->user?->email }}</small>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $accountUser->member_type)) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $accountUser->access_level)) }}</td>
                            <td>{{ $accountUser->can_login ? 'Yes' : 'No' }}</td>
                            <td>{{ $accountUser->branch?->name ?: '-' }}</td>
                            <td>{{ $accountUser->designation?->title ?: '-' }}</td>
                            <td>{{ ucwords($accountUser->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No account users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
