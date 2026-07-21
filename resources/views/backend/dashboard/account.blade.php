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
                ['Work orders', $workOrdersCount, 'bi-clipboard-check', 'warning'],
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
    .account-dashboard .account-hero { border-radius:18px; color:#fff; overflow:hidden; }
    .account-dashboard .dashboard-card { border:0; border-radius:14px; box-shadow:0 5px 22px rgba(15,23,42,.07); }
    .account-dashboard .metric-icon { width:46px; height:46px; display:grid; place-items:center; border-radius:12px; font-size:1.2rem; }
    .account-dashboard .metric-number { font-size:1.7rem; line-height:1; font-weight:700; color:#172554; }
    .account-dashboard .quick-action { border:1px solid #e2e8f0; border-radius:12px; color:#334155; text-decoration:none; transition:.15s ease; }
    .account-dashboard .quick-action:hover { border-color:#93c5fd; background:#eff6ff; transform:translateY(-1px); }
    .account-dashboard .usage-bar { height:7px; }
</style>

<div class="container-fluid py-4 account-dashboard">
    <div class="account-hero p-4 p-lg-5 mb-4" style="background:{{ $roleUi['gradient'] }}">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="text-uppercase small fw-semibold opacity-75 mb-2" style="letter-spacing:.12em">{{ $roleUi['eyebrow'] }}</div>
                <h2 class="fw-bold mb-2">{{ $roleUi['title'] }}</h2>
                <p class="mb-0 text-white-50">{{ $roleUi['subtitle'] }}</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex align-items-center gap-3 bg-white bg-opacity-10 rounded-3 px-3 py-3 text-start">
                    <i class="bi {{ $roleUi['icon'] }} fs-2"></i>
                    <div><div class="fw-semibold">{{ $planUsageAccount?->account_name }}</div><small class="text-white-50">{{ $planUsageSummary['plan_name'] ?: 'No active plan' }}</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($roleUi['metrics'] as [$label, $value, $icon, $colour])
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-card h-100"><div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div><div class="text-muted small mb-2">{{ $label }}</div><div class="metric-number">{{ number_format($value) }}</div></div>
                    <div class="metric-icon bg-{{ $colour }} bg-opacity-10 text-{{ $colour }}"><i class="bi {{ $icon }}"></i></div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white border-0 px-4 pt-4"><h5 class="mb-1">Quick actions</h5><div class="text-muted small">Common tasks for your role</div></div>
                <div class="card-body px-4"><div class="row g-3">
                    @can('create properties')
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.properties.quick') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-house-add fs-4 text-primary"></i><span class="fw-semibold">Add property</span></a></div>
                    @endcan
                    @can('view properties')
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.properties.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-buildings fs-4 text-info"></i><span class="fw-semibold">View properties</span></a></div>
                    @endcan
                    @can('manage tenancies')
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.tenancies.all') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-key fs-4 text-success"></i><span class="fw-semibold">Tenancies</span></a></div>
                    @endcan
                    @canany(['view property repair', 'edit property repair', 'create property repair'])
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.property_repairs.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-tools fs-4 text-danger"></i><span class="fw-semibold">Repair issues</span></a></div>
                    @endcanany
                    @if(in_array($dashboardRole, ['estate_agent', 'property_manager'], true))
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.branches.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-diagram-3 fs-4 text-warning"></i><span class="fw-semibold">Branches</span></a></div>
                    @endif
                    @if(in_array($dashboardRole, ['estate_agent', 'staff'], true))
                        <div class="col-sm-6 col-lg-4"><a href="{{ route('admin.users.index') }}" class="quick-action p-3 d-flex align-items-center gap-3 h-100"><i class="bi bi-people fs-4 text-secondary"></i><span class="fw-semibold">Contacts</span></a></div>
                    @endif
                </div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white border-0 px-4 pt-4"><h5 class="mb-1">Plan usage</h5><div class="text-muted small">{{ $planUsageSummary['plan_name'] }} · {{ ucwords($planUsageSummary['subscription_status'] ?? '') }}</div></div>
                <div class="card-body px-4">
                    @foreach(['properties'=>'Properties','branches'=>'Branches','staff'=>'Staff','property_managers'=>'Property managers'] as $key => $label)
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
                @forelse($recentProperties as $property)<div class="border-bottom py-3"><div class="fw-semibold">{{ $property->prop_name ?: $property->line_1 ?: 'Property #'.$property->id }}</div><small class="text-muted">{{ implode(', ', array_filter([$property->city, $property->postcode])) ?: $property->prop_ref_no }}</small></div>@empty<div class="text-muted text-center py-4">No properties yet.</div>@endforelse
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100"><div class="card-header bg-white border-0 px-4 pt-4 d-flex justify-content-between"><div><h5 class="mb-1">Repair activity</h5><small class="text-muted">Most recent issues</small></div>@can('view property repair')<a href="{{ route('admin.property_repairs.index') }}" class="small">View all</a>@endcan</div><div class="card-body px-4">
                @forelse($recentRepairs as $repair)<div class="d-flex justify-content-between gap-2 border-bottom py-3"><div><div class="fw-semibold">{{ $repair->reference_number ?: 'Repair #'.$repair->id }}</div><small class="text-muted">{{ $repair->property?->prop_name ?: $repair->property?->line_1 ?: 'No property' }}</small></div><span class="badge bg-secondary bg-opacity-10 text-secondary align-self-center">{{ $repair->status ?: 'Pending' }}</span></div>@empty<div class="text-muted text-center py-4">No repair activity.</div>@endforelse
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card dashboard-card h-100"><div class="card-header bg-white border-0 px-4 pt-4 d-flex justify-content-between"><div><h5 class="mb-1">Recent tenancies</h5><small class="text-muted">Latest tenancy records</small></div>@can('manage tenancies')<a href="{{ route('admin.tenancies.all') }}" class="small">View all</a>@endcan</div><div class="card-body px-4">
                @forelse($recentTenancies as $tenancy)<div class="d-flex justify-content-between gap-2 border-bottom py-3"><div><div class="fw-semibold">{{ $tenancy->property?->prop_name ?: $tenancy->property?->line_1 ?: 'Tenancy #'.$tenancy->id }}</div><small class="text-muted">{{ $tenancy->move_in ? 'Move in '.\Illuminate\Support\Carbon::parse($tenancy->move_in)->format('d M Y') : 'No move-in date' }}</small></div><span class="badge {{ $tenancy->status === 'Active' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 {{ $tenancy->status === 'Active' ? 'text-success' : 'text-secondary' }} align-self-center">{{ $tenancy->status }}</span></div>@empty<div class="text-muted text-center py-4">No tenancies yet.</div>@endforelse
            </div></div>
        </div>
    </div>
</div>
