@php
    $tpTabs = [
        ['label' => 'Home', 'route' => 'backend.home', 'match' => ['backend.home'], 'icon' => 'bi-house'],
        ['label' => 'Rent', 'route' => 'tenant.rent', 'match' => ['tenant.rent', 'tenant.rent.*'], 'icon' => 'bi-cash-stack'],
        ['label' => 'Repairs', 'route' => 'tenant.maintenance', 'match' => ['tenant.maintenance', 'admin.property_repairs.create'], 'icon' => 'bi-tools'],
        ['label' => 'Docs', 'route' => 'tenant.documents', 'match' => ['tenant.documents'], 'icon' => 'bi-folder'],
        ['label' => 'Me', 'route' => 'tenant.profile', 'match' => ['tenant.profile', 'tenant.notifications', 'tenant.tenancy', 'tenant.calendar'], 'icon' => 'bi-person'],
    ];
@endphp
<nav class="tp-bottom-nav" aria-label="Tenant phone navigation">
    @foreach($tpTabs as $tab)
        @php
            $active = false;
            foreach ($tab['match'] as $pattern) {
                if (request()->routeIs($pattern)) {
                    $active = true;
                    break;
                }
            }
        @endphp
        <a href="{{ route($tab['route']) }}" class="tp-bottom-nav__item{{ $active ? ' is-active' : '' }}">
            <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
