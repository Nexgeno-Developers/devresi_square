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
                <strong data-dash-label="Open repairs">{{ number_format($crmDashboard['open_repairs']) }}</strong>
                <small>Reported through to completion</small>
            </a>
            <a href="{{ route('admin.compliance.index') }}" class="crm-stat {{ $crmDashboard['compliance_ok'] ? '' : 'is-alert' }}">
                <span class="crm-kicker">Compliance</span>
                <strong class="crm-word">{{ $crmDashboard['compliance_ok'] ? 'Up to date' : 'Needs attention' }}</strong>
                <small>{{ $crmDashboard['compliance_ok'] ? 'No immediate actions' : 'Gas, EPC or EICR missing, expired, or due within 60 days' }}</small>
            </a>
        </div>

    <section class="crm-work" aria-labelledby="crm-work-title">
            <header class="crm-work-head">
                <h3 id="crm-work-title">Needs your attention</h3>
                <p>{{ $actionCount }} {{ \Illuminate\Support\Str::plural('action', $actionCount) }}</p>
            </header>
            @forelse($crmDashboard['actions'] as $action)
                <article class="crm-job {{ ($action['tone'] ?? '') === 'alert' ? 'is-alert' : '' }}"
                    @if(! empty($action['alert'])) data-alert-{{ $action['alert'] }}="{{ (int) $action['count'] }}" @endif>
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
                @if($propertyCount === 0)
                    {{-- A brand-new landlord: name the next steps instead of "all caught up". --}}
                    <article class="crm-job" data-next-action="add-property">
                        <p class="crm-kind">Step 1</p>
                        <div class="crm-job-copy"><strong>Add your first property</strong><p>Everything else hangs off the home.</p></div>
                        <a href="{{ property_create_url() }}" class="btn btn-sm lw-btn-primary">Add a property</a>
                    </article>
                    <article class="crm-job" data-next-action="add-tenancy">
                        <p class="crm-kind">Step 2</p>
                        <div class="crm-job-copy"><strong>Add a tenancy</strong><p>Invite the tenant so rent and repairs reach you here.</p></div>
                        <a href="{{ route('admin.tenancies.create') }}" class="btn btn-sm lw-btn-secondary">Add a tenancy</a>
                    </article>
                    <article class="crm-job" data-next-action="raise-repair">
                        <p class="crm-kind">Step 3</p>
                        <div class="crm-job-copy"><strong>Raise a repair</strong><p>Log a job and send it to a contractor.</p></div>
                        <a href="{{ route('admin.property_repairs.create') }}" class="btn btn-sm lw-btn-secondary">Raise a repair</a>
                    </article>
                @else
                    <div class="crm-clear">
                        <strong>You're all caught up</strong>
                        <p>Nothing across the portfolio needs a decision right now.</p>
                    </div>
                @endif
            @endforelse
    </section>
</div>
