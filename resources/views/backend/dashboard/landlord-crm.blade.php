<div class="container-fluid py-4 account-dashboard lw-page">
    <div class="lw-hero mb-4">
        <h2 class="fw-bold mb-2">{{ $crmDashboard['greeting'] }}</h2>
        <p class="text-muted mb-0">One property. One record. Everything connected.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <a href="{{ route('admin.properties.index') }}" class="lw-metric-link">
                <div class="card dashboard-card h-100"><div class="card-body">
                    <div class="metric-number">{{ number_format($crmDashboard['properties']) }} Properties</div>
                    <div class="text-muted small mt-2">{{ $crmDashboard['occupied'] }} occupied · {{ $crmDashboard['notice'] }} notice · {{ $crmDashboard['vacant'] }} vacant</div>
                </div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl">
            <a href="{{ route('admin.properties.index') }}" class="lw-metric-link">
                <div class="card dashboard-card h-100"><div class="card-body">
                    <div class="metric-number">{{ $crmDashboard['occupancy'] }}% Occupied</div>
                    <div class="text-muted small mt-2">{{ $crmDashboard['occupied'] }} occupied {{ \Illuminate\Support\Str::plural('property', $crmDashboard['occupied']) }}</div>
                </div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl">
            <a href="{{ route('admin.finance.index') }}" class="lw-metric-link">
                <div class="card dashboard-card h-100"><div class="card-body">
                    <div class="text-muted small mb-2">Rent This Month</div>
                    <div class="metric-number">{{ rs_money($crmDashboard['received']) }} Received</div>
                    <div class="text-muted small mt-2">{{ rs_money($crmDashboard['outstanding']) }} outstanding</div>
                </div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl">
            <a href="{{ route('admin.property_repairs.index') }}" class="lw-metric-link">
                <div class="card dashboard-card h-100"><div class="card-body">
                    <div class="metric-number">{{ number_format($crmDashboard['open_repairs']) }} Open Repairs</div>
                    <div class="text-muted small mt-2">Reported through to completion</div>
                </div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl">
            <a href="{{ route('admin.compliance.index') }}" class="lw-metric-link">
                <div class="card dashboard-card h-100"><div class="card-body">
                    <div class="text-muted small mb-2">Compliance</div>
                    <div class="metric-number">{{ $crmDashboard['compliance_ok'] ? 'Up to Date' : 'Needs Attention' }}</div>
                    <div class="text-muted small mt-2">{{ $crmDashboard['compliance_ok'] ? 'No immediate actions' : 'Review certificates and dates' }}</div>
                </div></div>
            </a>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header bg-white border-0 px-4 pt-4">
            <h5 class="mb-1">Needs Your Attention ({{ count($crmDashboard['actions']) }} {{ \Illuminate\Support\Str::plural('action', count($crmDashboard['actions'])) }})</h5>
        </div>
        <div class="card-body px-4">
            @forelse($crmDashboard['actions'] as $action)
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap border-bottom py-3">
                    <div>{{ $action['text'] }}</div>
                    <a href="{{ $action['url'] }}" class="btn btn-sm lw-btn-primary">{{ $action['cta'] }}</a>
                </div>
            @empty
                <div class="py-4 text-center">
                    <div class="fw-semibold">You're All Caught Up</div>
                    <p class="text-muted mb-0">There are no urgent actions across your portfolio.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
