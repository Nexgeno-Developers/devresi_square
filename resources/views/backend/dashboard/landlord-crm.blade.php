@php
    $propertyCount = (int) $crmDashboard['properties'];
    $actionCount = count($crmDashboard['actions']);
@endphp
<div class="container-fluid py-4 account-dashboard lw-page crm-desk">
    <header class="crm-head">
        <h2>{{ $crmDashboard['greeting'] }}</h2>
        <p>{{ now()->format('l j F') }} · {{ $propertyCount }} {{ \Illuminate\Support\Str::plural('property', $propertyCount) }}</p>
    </header>

    <div class="crm-strip" aria-label="Portfolio status">
            <a href="{{ route('admin.properties.index') }}" class="crm-stat">
                <span class="crm-kicker">Portfolio</span>
                <strong>{{ number_format($propertyCount) }}</strong>
                <em>{{ $crmDashboard['occupancy'] }}% occupied</em>
                <span class="crm-occ" aria-hidden="true"><i style="width: {{ (int) $crmDashboard['occupancy'] }}%"></i></span>
                <small>{{ $crmDashboard['occupied'] }} occupied · {{ $crmDashboard['notice'] }} notice · {{ $crmDashboard['vacant'] }} vacant</small>
            </a>
            <a href="{{ route('admin.finance.index') }}" class="crm-stat {{ $crmDashboard['outstanding'] > 0 ? 'is-alert' : '' }}">
                <span class="crm-kicker">Rent this month</span>
                <strong>{{ rs_money($crmDashboard['received']) }}</strong>
                <small>{{ rs_money($crmDashboard['outstanding']) }} outstanding</small>
            </a>
            <a href="{{ route('admin.property_repairs.index') }}" class="crm-stat">
                <span class="crm-kicker">Open repairs</span>
                <strong>{{ number_format($crmDashboard['open_repairs']) }}</strong>
                <small>Reported through to completion</small>
            </a>
            <a href="{{ route('admin.compliance.index') }}" class="crm-stat {{ $crmDashboard['compliance_ok'] ? '' : 'is-alert' }}">
                <span class="crm-kicker">Compliance</span>
                <strong class="crm-word">{{ $crmDashboard['compliance_ok'] ? 'Up to date' : 'Needs attention' }}</strong>
                <small>{{ $crmDashboard['compliance_ok'] ? 'No immediate actions' : 'Review certificates and dates' }}</small>
            </a>
        </div>

    <section class="crm-work" aria-labelledby="crm-work-title">
            <header class="crm-work-head">
                <h3 id="crm-work-title">Needs your attention</h3>
                <p>{{ $actionCount }} {{ \Illuminate\Support\Str::plural('action', $actionCount) }}</p>
            </header>
            @forelse($crmDashboard['actions'] as $action)
                <article class="crm-job {{ ($action['tone'] ?? '') === 'alert' ? 'is-alert' : '' }}">
                    <p class="crm-kind">{{ $action['kind'] ?? 'Action' }}</p>
                    <div class="crm-job-copy">
                        <strong>{{ $action['place'] ?? $action['text'] }}</strong>
                        @if(! empty($action['detail']))
                            <p>{{ $action['detail'] }}</p>
                        @endif
                    </div>
                    <a href="{{ $action['url'] }}" class="btn btn-sm lw-btn-primary">{{ $action['cta'] }}</a>
                </article>
            @empty
                <div class="crm-clear">
                    <strong>You're all caught up</strong>
                    <p>Nothing across the portfolio needs a decision right now.</p>
                </div>
            @endforelse
    </section>
</div>
