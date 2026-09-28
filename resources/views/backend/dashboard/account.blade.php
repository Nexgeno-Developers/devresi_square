@php
    $roleUi = [
        'landlord' => [
            'eyebrow' => 'Landlord workspace', 'title' => 'Your property portfolio',
            'subtitle' => 'Keep an eye on occupancy, maintenance and the day-to-day health of your properties.',
            'gradient' => 'linear-gradient(120deg,#064e3b,#0f766e)', 'icon' => 'bi-house-heart',
            'metrics' => [
                ['Portfolio properties', $propertiesCount, 'bi-buildings', 'primary'],
                ['Active tenancies', $activeTenanciesCount, 'bi-key', 'success'],
                ['Open repairs', $openRepairsCount, 'bi-tools', 'danger'],
                ['People', $usersCount, 'bi-people', 'info'],
            ],
        ],
        'estate_agent' => [
            'eyebrow' => 'Estate agency workspace', 'title' => 'Agency operations',
            'subtitle' => 'A clear view of managed stock, active tenancies, branches and your team.',
            'gradient' => 'linear-gradient(120deg,#172554,#1d4ed8)', 'icon' => 'bi-building-check',
            'metrics' => [
                ['Managed properties', $propertiesCount, 'bi-buildings', 'primary'],
                ['Active tenancies', $activeTenanciesCount, 'bi-key', 'success'],
                ['Branches', $branchesCount, 'bi-diagram-3', 'info'],
                ['Team members', $staffCount, 'bi-people', 'warning'],
            ],
        ],
        'property_manager' => [
            'eyebrow' => 'Property manager workspace', 'title' => 'Today’s property operations',
            'subtitle' => 'Prioritise repairs, work orders and tenancy activity across the portfolio.',
            'gradient' => 'linear-gradient(120deg,#3f1d75,#7e22ce)', 'icon' => 'bi-clipboard2-pulse',
            'metrics' => [
                ['Properties', $propertiesCount, 'bi-buildings', 'primary'],
                ['Open repairs', $openRepairsCount, 'bi-tools', 'danger'],
                ['Work orders', $workOrdersCount, 'bi-clipboard-check', 'warning'],
                ['Active tenancies', $activeTenanciesCount, 'bi-key', 'success'],
            ],
        ],
        'staff' => [
            'eyebrow' => 'Team workspace', 'title' => 'Your operational overview',
            'subtitle' => 'Quick access to properties, customer activity, repairs and work in progress.',
            'gradient' => 'linear-gradient(120deg,#713f12,#d97706)', 'icon' => 'bi-person-workspace',
            'metrics' => [
                ['Properties', $propertiesCount, 'bi-buildings', 'primary'],
                ['Open repairs', $openRepairsCount, 'bi-tools', 'danger'],
                ['Work orders', $workOrdersCount, 'bi-clipboard-check', 'warning'],
                ['Account users', $usersCount, 'bi-people', 'info'],
            ],
        ],
        'account' => [
            'eyebrow' => 'Account workspace', 'title' => 'Business overview',
            'subtitle' => 'Your properties, customers and operational activity in one place.',
            'gradient' => 'linear-gradient(120deg,#0f172a,#334155)', 'icon' => 'bi-grid',
            'metrics' => [
                ['Properties', $propertiesCount, 'bi-buildings', 'primary'],
                ['Active tenancies', $activeTenanciesCount, 'bi-key', 'success'],
                ['Open repairs', $openRepairsCount, 'bi-tools', 'danger'],
                ['Work orders', $workOrdersCount, 'bi-clipboard-check', 'warning'],
            ],
        ],
    ][$dashboardRole] ?? null;
@endphp

<style>
    .account-dashboard .metric-icon { width:46px; height:46px; display:grid; place-items:center; border-radius:12px; font-size:1.2rem; }
    .account-dashboard .metric-number { font-size:1.7rem; line-height:1; font-weight:700; }
    .account-dashboard .quick-action { border:1px solid var(--lw-line, #e2e8f0); border-radius:12px; color:inherit; text-decoration:none; }
    .account-dashboard .usage-bar { height:7px; }
</style>

<div class="container-fluid py-4 account-dashboard lw-page">
    <div class="lw-hero mb-4" @unless(is_landlord_plan_user()) style="background:{{ $roleUi['gradient'] }}; color:#fff;" @endunless>
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="text-uppercase small fw-semibold opacity-75 mb-2" style="letter-spacing:.12em">{{ $roleUi['eyebrow'] }}</div>
                <h2 class="fw-bold mb-2">{{ $roleUi['title'] }}</h2>
                <p class="mb-0 {{ is_landlord_plan_user() ? 'text-muted' : 'text-white-50' }}">{{ $roleUi['subtitle'] }}</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex align-items-center gap-3 rounded-3 px-3 py-3 text-start {{ is_landlord_plan_user() ? '' : 'bg-white bg-opacity-10' }}">
                    <i class="bi {{ $roleUi['icon'] }} fs-2"></i>
                    <div><div class="fw-semibold">{{ $planUsageAccount?->account_name }}</div><small class="{{ is_landlord_plan_user() ? 'text-muted' : 'text-white-50' }}">{{ $planUsageSummary['plan_name'] ?: 'No active plan' }}</small></div>
                </div>
            </div>
        </div>
    </div>

    @if(! empty($pendingCorrectionCount))
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4" data-alert-corrections="{{ $pendingCorrectionCount }}">
            <div>
                <strong>{{ $pendingCorrectionCount }} tenancy correction {{ \Illuminate\Support\Str::plural('request', $pendingCorrectionCount) }} waiting</strong>
                <div class="small mb-0">A tenant asked you to check rent, dates or deposit before they confirm the let.</div>
            </div>
            @if(! empty($pendingCorrectionTenancyId))
                <a href="{{ route('admin.tenancies.show', $pendingCorrectionTenancyId) }}" class="btn btn-sm btn-dark">Review</a>
            @endif
        </div>
    @endif

    @if(! empty($overdueRentCount))
        <div class="alert alert-danger d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4" data-alert-arrears="{{ $overdueRentCount }}">
            <div>
                <strong>{{ $overdueRentCount }} overdue rent {{ \Illuminate\Support\Str::plural('invoice', $overdueRentCount) }}</strong>
                <div class="small mb-0">Open balance past the due date.</div>
            </div>
            <a href="{{ route('admin.finance.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-dark">View arrears</a>
        </div>
    @endif

    @if(! empty($complianceAttentionCount))
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4" data-alert-certificates="{{ $complianceAttentionCount }}">
            <div>
                <strong>{{ $complianceAttentionCount }} {{ \Illuminate\Support\Str::plural('certificate', $complianceAttentionCount) }} {{ $complianceAttentionCount === 1 ? 'needs' : 'need' }} you</strong>
                <div class="small mb-0">Gas, EPC or EICR expired or due within 60 days.</div>
            </div>
            <a href="{{ route('admin.compliance.index') }}" class="btn btn-sm btn-dark">Review certificates</a>
        </div>
    @endif

    @if(! empty($depositAttentionCount))
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4" data-alert-deposits="{{ $depositAttentionCount }}">
            <div>
                <strong>{{ $depositAttentionCount }} {{ \Illuminate\Support\Str::plural('deposit', $depositAttentionCount) }} {{ $depositAttentionCount === 1 ? 'needs' : 'need' }} you</strong>
                <div class="small mb-0">Protection scheme, dates or prescribed information incomplete.</div>
            </div>
            <a href="{{ ! empty($depositAttentionTenancyId) ? route('admin.tenancies.show', $depositAttentionTenancyId) : route('admin.tenancies.all') }}" class="btn btn-sm btn-dark">Review deposit</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @php
            $metricHrefs = [
                'Portfolio properties' => route('admin.properties.index'),
                'People' => route('admin.people.index'),
                'Active tenancies' => route('admin.tenancies.all', ['status' => 'Active']),
                'Open repairs' => route('admin.property_repairs.index'),
                'Contacts' => route('admin.users.index'),
            ];
        @endphp
        @foreach($roleUi['metrics'] as [$label, $value, $icon, $colour])
            <div class="col-sm-6 col-xl-3">
                @if(! empty($metricHrefs[$label]))
                    <a href="{{ $metricHrefs[$label] }}" class="lw-metric-link">
                @endif
                <div class="card dashboard-card h-100"><div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div><div class="text-muted small mb-2">{{ $label }}</div><div class="metric-number" data-dash-label="{{ $label }}">{{ number_format($value) }}</div></div>
                    <div class="metric-icon bg-{{ $colour }} bg-opacity-10 text-{{ $colour }}"><i class="bi {{ $icon }}"></i></div>
                </div></div>
                @if(! empty($metricHrefs[$label]))
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white border-0 px-4 pt-4"><h5 class="mb-1">Quick actions</h5><div class="text-muted small">Common tasks for your role</div></div>
                <div class="card-body px-4"><div class="row g-3">
                    @can('create properties')
                        <div class="col-sm-6 col-lg-4"><a href="{{ property_create_url() }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-house-add fs-4 text-primary"></i><span class="fw-semibold">Add property</span></a></div>
                    @endcan
                    @canany(['view properties', 'create properties', 'edit properties'])
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.properties.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-buildings fs-4 text-info"></i><span class="fw-semibold">View properties</span></a></div>
                    @endcanany
                    @if(auth()->user()->can('manage tenancies') || is_landlord_plan_user())
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.tenancies.all') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-key fs-4 text-success"></i><span class="fw-semibold">Tenancies</span></a></div>
                    @endif
                    @if(auth()->user()->canAny(['view property repair', 'edit property repair', 'create property repair']) || is_landlord_plan_user())
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.property_repairs.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-tools fs-4 text-danger"></i><span class="fw-semibold">Repair issues</span></a></div>
                    @endif
                    @if(is_landlord_plan_user())
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.finance.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-receipt fs-4 text-success"></i><span class="fw-semibold">Finance</span></a></div>
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.people.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-person-plus fs-4 text-primary"></i><span class="fw-semibold">Invite tenant</span></a></div>
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.documents.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-folder2-open fs-4 text-warning"></i><span class="fw-semibold">Documents</span></a></div>
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('backend.events.calendar') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-calendar-event fs-4 text-info"></i><span class="fw-semibold">Calendar</span></a></div>
                    @endif
                    @if(in_array($dashboardRole, ['estate_agent', 'property_manager'], true) && ! is_landlord_plan_user())
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.branches.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-diagram-3 fs-4 text-warning"></i><span class="fw-semibold">Branches</span></a></div>
                    @endif
                    @if(can_view_contacts())
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.users.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-people fs-4 text-secondary"></i><span class="fw-semibold">Contacts</span></a></div>
                    @endif
                </div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white border-0 px-4 pt-4"><h5 class="mb-1">Plan usage</h5><div class="text-muted small">{{ $planUsageSummary['plan_name'] }} · {{ ucwords($planUsageSummary['subscription_status'] ?? '') }}</div></div>
                <div class="card-body px-4">
                    @php
                        $usageMeters = is_landlord_plan_user()
                            ? ['properties' => 'Properties']
                            : ['properties' => 'Properties', 'branches' => 'Branches', 'staff' => 'Staff', 'property_managers' => 'Property managers'];
                    @endphp
                    @foreach($usageMeters as $key => $label)
                        @php($usage = $planUsageSummary[$key])
                        @php($percent = $usage['limit'] > 0 ? min(100, round(($usage['used'] / $usage['limit']) * 100)) : 100)
                        <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span>{{ $label }}</span><strong>{{ $usage['used'] }} / {{ $usage['limit'] }}</strong></div><div class="progress usage-bar"><div class="progress-bar {{ $usage['can_add'] ? 'bg-success' : 'bg-danger' }}" style="width:{{ $percent }}%"></div></div></div>
                    @endforeach
                    @if($planUsageSummary['trial_ends_at'])<div class="alert alert-info small mb-0"><i class="bi bi-clock me-1"></i> Trial ends {{ $planUsageSummary['trial_ends_at']->format('d M Y') }}</div>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card dashboard-card h-100"><div class="card-header bg-white border-0 px-4 pt-4 d-flex justify-content-between"><div><h5 class="mb-1">Recent properties</h5><small class="text-muted">Latest portfolio additions</small></div><a href="{{ route('admin.properties.index') }}" class="small">View all</a></div><div class="card-body px-4">
                @forelse($recentProperties as $property)<div class="border-bottom py-3"><div class="fw-semibold">{{ rs_property_title($property) }}</div><small class="text-muted">{{ $property->short_address ?: '—' }}</small></div>@empty<div class="text-center py-4" data-next-action="add-property"><p class="mb-2">No homes yet.</p><a href="{{ property_create_url() }}" class="btn btn-sm lw-btn-primary">Add a property</a></div>@endforelse
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100"><div class="card-header bg-white border-0 px-4 pt-4 d-flex justify-content-between"><div><h5 class="mb-1">Repair activity</h5><small class="text-muted">Most recent issues</small></div>@if(is_landlord_plan_user() || auth()->user()->can('view property repair'))<a href="{{ route('admin.property_repairs.index') }}" class="small">View all</a>@endif</div><div class="card-body px-4">
                @forelse($recentRepairs as $repair)<div class="d-flex justify-content-between gap-2 border-bottom py-3"><div><div class="fw-semibold">{{ rs_property_title($repair->property) }}</div><small class="text-muted">{{ \Illuminate\Support\Str::limit($repair->description ?: 'Repair', 80) }}</small>@if($repair->reference_number)<div class="text-muted small">{{ $repair->reference_number }}</div>@endif</div><span class="badge bg-secondary bg-opacity-10 text-secondary align-self-center">{{ $repair->status ?: 'Pending' }}</span></div>@empty<div class="text-center py-4" data-next-action="raise-repair"><p class="mb-2">No repairs yet.</p><a href="{{ route('admin.property_repairs.create') }}" class="btn btn-sm lw-btn-primary">Raise a repair</a></div>@endforelse
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100"><div class="card-header bg-white border-0 px-4 pt-4 d-flex justify-content-between"><div><h5 class="mb-1">Recent tenancies</h5><small class="text-muted">Latest tenancy records</small></div>@if(is_landlord_plan_user() || auth()->user()->can('manage tenancies'))<a href="{{ route('admin.tenancies.all') }}" class="small">View all</a>@endif</div><div class="card-body px-4">
                @forelse($recentTenancies as $tenancy)<div class="d-flex justify-content-between gap-2 border-bottom py-3"><div><div class="fw-semibold">{{ rs_property_title($tenancy->property) }}</div><small class="text-muted">{{ $tenancy->move_in ? 'Move in '.rs_date($tenancy->move_in) : 'No move-in date' }}</small></div><span class="badge {{ $tenancy->status === 'Active' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 {{ $tenancy->status === 'Active' ? 'text-success' : 'text-secondary' }} align-self-center">{{ $tenancy->status }}</span></div>@empty<div class="text-center py-4" data-next-action="add-tenancy"><p class="mb-2">No tenancies yet.</p><a href="{{ route('admin.tenancies.create') }}" class="btn btn-sm lw-btn-primary">Add a tenancy</a></div>@endforelse
            </div></div>
        </div>
    </div>
</div>
