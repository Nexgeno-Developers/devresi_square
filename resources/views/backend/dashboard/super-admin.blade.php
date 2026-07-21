<style>
    .saas-dashboard { --saas-primary: #172554; --saas-accent: #f97316; }
    .saas-dashboard .hero { background: linear-gradient(120deg, #0f172a, #1e3a8a); border-radius: 18px; color: #fff; overflow: hidden; }
    .saas-dashboard .metric-card, .saas-dashboard .panel { border: 0; border-radius: 14px; box-shadow: 0 5px 22px rgba(15, 23, 42, .07); }
    .saas-dashboard .metric-icon { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 12px; font-size: 1.15rem; }
    .saas-dashboard .metric-value { color: var(--saas-primary); font-size: 1.65rem; font-weight: 700; line-height: 1.1; }
    .saas-dashboard .status-dot { width: 9px; height: 9px; display: inline-block; border-radius: 50%; }
    .saas-dashboard .table > :not(caption) > * > * { padding: .85rem .75rem; }
    .saas-dashboard .quick-link { color: inherit; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease; }
    .saas-dashboard .quick-link:hover { transform: translateY(-2px); box-shadow: 0 9px 26px rgba(15, 23, 42, .12); }
</style>

<div class="container-fluid py-4 saas-dashboard">
    <div class="hero p-4 p-lg-5 mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
        <div>
            <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.12em;color:#93c5fd;">SaaS command centre</div>
            <h2 class="fw-bold mb-2">Super Admin Dashboard</h2>
            <p class="mb-0 text-white-50">Account growth, subscription revenue and customer health at a glance.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('backend.saas.accounts.index') }}" class="btn btn-light"><i class="bi bi-buildings me-1"></i> Accounts</a>
            <a href="{{ route('backend.saas.subscriptions.index') }}" class="btn btn-outline-light"><i class="bi bi-credit-card me-1"></i> Subscriptions</a>
            <a href="{{ route('admin.registrations.index') }}" class="btn btn-outline-light"><i class="bi bi-person-check me-1"></i> Registrations</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Monthly recurring revenue', 'value' => '£'.number_format($saasMetrics['mrr'], 2), 'note' => 'ARR £'.number_format($saasMetrics['arr'], 2), 'icon' => 'bi-graph-up-arrow', 'colour' => 'success'],
            ['label' => 'Total accounts', 'value' => number_format($saasMetrics['total_accounts']), 'note' => $saasMetrics['new_accounts_this_month'].' added this month', 'icon' => 'bi-buildings', 'colour' => 'primary'],
            ['label' => 'Active accounts', 'value' => number_format($saasMetrics['active_accounts']), 'note' => $saasMetrics['trial_accounts'].' currently trialing', 'icon' => 'bi-check-circle', 'colour' => 'info'],
            ['label' => 'Needs attention', 'value' => number_format($saasMetrics['past_due_accounts']), 'note' => 'Past-due accounts', 'icon' => 'bi-exclamation-triangle', 'colour' => 'danger'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="card metric-card h-100">
                    <div class="card-body d-flex justify-content-between gap-3">
                        <div>
                            <div class="text-muted small mb-2">{{ $metric['label'] }}</div>
                            <div class="metric-value">{{ $metric['value'] }}</div>
                            <div class="text-muted small mt-2">{{ $metric['note'] }}</div>
                        </div>
                        <div class="metric-icon bg-{{ $metric['colour'] }} bg-opacity-10 text-{{ $metric['colour'] }}"><i class="bi {{ $metric['icon'] }}"></i></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <a class="card panel quick-link h-100" href="{{ route('admin.registrations.index', ['status' => 'verified']) }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-plus"></i></div>
                    <div><div class="fw-bold fs-4">{{ $saasMetrics['pending_approvals'] }}</div><div class="text-muted small">Registrations awaiting approval</div></div>
                    <i class="bi bi-arrow-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a class="card panel quick-link h-100" href="{{ route('backend.saas.plans.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                    <div><div class="fw-bold fs-4">{{ $saasMetrics['active_plans'] }}</div><div class="text-muted small">Active subscription plans</div></div>
                    <i class="bi bi-arrow-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <div class="card panel h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="metric-icon bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass-split"></i></div>
                    <div><div class="fw-bold fs-4">{{ $expiringTrials->count() }}</div><div class="text-muted small">Trials ending in the next 7 days</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card panel h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <div><h5 class="mb-1">Recent accounts</h5><div class="text-muted small">Newest SaaS customers</div></div>
                    <a href="{{ route('backend.saas.accounts.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="text-muted small"><tr><th>Account</th><th>Plan</th><th>Status</th><th>Created</th><th></th></tr></thead>
                            <tbody>
                            @forelse($recentAccounts as $account)
                                @php($subscription = $account->currentSubscription ?? $account->latestSubscription)
                                @php($statusColour = ['active'=>'success','trialing'=>'info','past_due'=>'danger','suspended'=>'warning','cancelled'=>'secondary'][$account->status] ?? 'secondary')
                                <tr>
                                    <td><div class="fw-semibold">{{ $account->account_name ?: 'Unnamed account' }}</div><small class="text-muted">{{ $account->billing_email ?: $account->owner?->email }}</small></td>
                                    <td>{{ $subscription?->plan?->name ?: '—' }}</td>
                                    <td><span class="badge bg-{{ $statusColour }} bg-opacity-10 text-{{ $statusColour }}">{{ ucwords(str_replace('_', ' ', $account->status)) }}</span></td>
                                    <td class="text-nowrap">{{ $account->created_at?->format('d M Y') }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('backend.saas.accounts.show', $account) }}" class="btn btn-sm btn-light" aria-label="View account"><i class="bi bi-eye"></i></a>
                                        <form action="{{ route('backend.saas.accounts.login-as', $account) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Login as account" aria-label="Login as account" @disabled(! $account->owner || ! $account->owner->can_login || ! $account->owner->status)>
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No SaaS accounts yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card panel h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Account health</h5><div class="text-muted small">Current status distribution</div></div>
                <div class="card-body px-4">
                    @forelse(['active'=>'success','trialing'=>'info','past_due'=>'danger','suspended'=>'warning','cancelled'=>'secondary'] as $status => $colour)
                        @php($count = (int) ($accountStatusCounts[$status] ?? 0))
                        @php($percentage = $totalAccounts > 0 ? round(($count / $totalAccounts) * 100) : 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1"><span><i class="status-dot bg-{{ $colour }} me-2"></i>{{ ucwords(str_replace('_', ' ', $status)) }}</span><strong>{{ $count }}</strong></div>
                            <div class="progress" style="height:6px"><div class="progress-bar bg-{{ $colour }}" style="width:{{ $percentage }}%"></div></div>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card panel h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Plan adoption</h5><div class="text-muted small">Live subscriptions by plan</div></div>
                <div class="card-body px-4">
                    @forelse($planDistribution as $plan)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                            <div><div class="fw-semibold">{{ $plan->name }}</div><small class="text-muted">{{ ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</small></div>
                            <span class="badge bg-primary rounded-pill">{{ $plan->live_subscriptions_count }}</span>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">No plans configured.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card panel h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Trials ending soon</h5><div class="text-muted small">Next seven days</div></div>
                <div class="card-body px-4">
                    @forelse($expiringTrials as $subscription)
                        <div class="d-flex justify-content-between gap-3 border-bottom py-3">
                            <div><a class="fw-semibold text-decoration-none" href="{{ route('backend.saas.subscriptions.show', $subscription) }}">{{ $subscription->account?->account_name ?: 'Unnamed account' }}</a><div class="text-muted small">{{ $subscription->plan?->name }}</div></div>
                            <span class="small text-danger text-nowrap">{{ $subscription->trial_ends_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4"><i class="bi bi-check-circle fs-2 d-block text-success mb-2"></i>No trials expire this week.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card panel h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><div><h5 class="mb-1">Registrations</h5><div class="text-muted small">Latest signup activity</div></div><a href="{{ route('admin.registrations.index') }}" class="small">View all</a></div>
                <div class="card-body px-4">
                    @forelse($recentRegistrations as $registration)
                        @php($registrationColour = ['approved'=>'success','verified'=>'warning','rejected'=>'danger'][$registration->status] ?? 'secondary')
                        <div class="d-flex justify-content-between gap-3 border-bottom py-3">
                            <div><a class="fw-semibold text-decoration-none" href="{{ route('admin.registrations.show', $registration->id) }}">{{ $registration->full_name }}</a><div class="text-muted small">{{ $registration->plan?->name ?: ucwords(str_replace('_', ' ', $registration->type)) }}</div></div>
                            <span class="badge bg-{{ $registrationColour }} bg-opacity-10 text-{{ $registrationColour }} align-self-center">{{ ucfirst($registration->status) }}</span>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">No registration activity.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
