{{--
    App-style bottom bar for phones and tablets (below 992px). The side menu is not
    shown there: these tabs are the everyday places for the role, and "More" opens
    the full menu (the same #menu list, with all permissions) as a sheet above the bar.
--}}
@php
    $bnUser = auth()->user();
    $bnTenant = is_tenant_portal_user();
    $bnLandlord = ! $bnTenant && is_landlord_plan_user();
    $bnContractor = ! $bnTenant && ! $bnLandlord && $bnUser->hasRole('Contractor');
    $bnSuper = ! $bnTenant && ! $bnLandlord && ! $bnContractor && $bnUser->hasRole('Super Admin');

    if ($bnTenant) {
        $bnLabel = 'Tenant phone navigation';
        $bnTabs = [
            ['label' => 'Home', 'route' => 'backend.home', 'match' => ['backend.home'], 'icon' => 'bi-house'],
            ['label' => 'Rent', 'route' => 'tenant.rent', 'match' => ['tenant.rent', 'tenant.rent.*'], 'icon' => 'bi-cash-stack'],
            ['label' => 'Repairs', 'route' => 'tenant.maintenance', 'match' => ['tenant.maintenance', 'admin.property_repairs.create'], 'icon' => 'bi-tools'],
            ['label' => 'Docs', 'route' => 'tenant.documents', 'match' => ['tenant.documents'], 'icon' => 'bi-folder'],
        ];
        // Tenants have no top-bar shortcut habit: keep Messages (with its unread dot) on the bar.
        if (\App\Support\Messaging\MessagingBadge::current()) {
            array_splice($bnTabs, 3, 0, [[
                'label' => 'Messages', 'route' => 'portal.messages.index', 'match' => ['portal.messages.*'],
                'icon' => 'bi-chat-dots', 'badge' => true,
            ]]);
        }
    } elseif ($bnLandlord) {
        $bnLabel = 'Phone navigation';
        $bnTabs = [
            ['label' => 'Home', 'route' => 'backend.dashboard', 'match' => ['backend.dashboard'], 'icon' => 'bi-grid'],
            // Tenancies live inside a property for a landlord, so they keep the Properties tab lit.
            ['label' => 'Properties', 'route' => 'admin.properties.index', 'match' => ['admin.properties.*', 'admin.tenancies.*'], 'icon' => 'bi-building'],
            ['label' => 'Rent', 'route' => 'admin.finance.index', 'match' => ['admin.finance.*'], 'icon' => 'bi-cash-stack'],
            ['label' => 'Repairs', 'route' => 'admin.property_repairs.index', 'match' => ['admin.property_repairs.*'], 'icon' => 'bi-tools'],
        ];
    } elseif ($bnContractor) {
        $bnLabel = 'Phone navigation';
        $bnTabs = [
            ['label' => 'Jobs', 'route' => 'contractor.jobs.index', 'match' => ['contractor.jobs.*'], 'icon' => 'bi-wrench-adjustable'],
        ];
        if ($bnUser->contractorProfile?->approval_status !== \App\Models\ContractorProfile::SUBMITTED) {
            $bnTabs[] = ['label' => 'Documents', 'route' => 'contractor.profile.edit', 'match' => ['contractor.profile.*'], 'icon' => 'bi-person-vcard'];
        }
        // Contractors chat with landlords and tenants too: same Messages tab and unread dot as tenants.
        if (\App\Support\Messaging\MessagingBadge::current()) {
            $bnTabs[] = [
                'label' => 'Messages', 'route' => 'contractor.messages.index', 'match' => ['contractor.messages.*'],
                'icon' => 'bi-chat-dots', 'badge' => true,
            ];
        }
    } elseif ($bnSuper) {
        $bnLabel = 'Phone navigation';
        $bnTabs = [
            ['label' => 'Accounts', 'route' => 'backend.saas.accounts.index', 'match' => ['backend.saas.accounts.*'], 'icon' => 'bi-people'],
            ['label' => 'Sign-ups', 'route' => 'admin.registrations.index', 'match' => ['admin.registrations.index', 'admin.registrations.show'], 'icon' => 'bi-person-plus'],
            ['label' => 'Contractors', 'route' => 'admin.registrations.contractors.index', 'match' => ['admin.registrations.contractors.*'], 'icon' => 'bi-person-check'],
            ['label' => 'Repair desk', 'route' => 'backend.saas.repair_desk.index', 'match' => ['backend.saas.repair_desk.*'], 'icon' => 'bi-tools'],
        ];
    } else {
        $bnLabel = 'Phone navigation';
        $bnTabs = [
            ['label' => 'Home', 'route' => 'backend.dashboard', 'match' => ['backend.dashboard'], 'icon' => 'bi-grid'],
        ];
        if ($bnUser->can('view properties')) {
            $bnTabs[] = ['label' => 'Properties', 'route' => 'admin.properties.index', 'match' => ['admin.properties.*'], 'icon' => 'bi-building'];
        }
        if ($bnUser->can('manage tenancies')) {
            $bnTabs[] = ['label' => 'Tenancies', 'route' => 'admin.tenancies.all', 'match' => ['admin.tenancies.*'], 'icon' => 'bi-house-door'];
        }
        if ($bnUser->can('view property repair')) {
            $bnTabs[] = ['label' => 'Repairs', 'route' => 'admin.property_repairs.index', 'match' => ['admin.property_repairs.*'], 'icon' => 'bi-tools'];
        }
    }

    $bnAnyActive = false;
    $bnTabs = collect($bnTabs)->map(function (array $tab) use (&$bnAnyActive) {
        $tab['active'] = collect($tab['match'])->contains(fn ($pattern) => request()->routeIs($pattern));
        $bnAnyActive = $bnAnyActive || $tab['active'];

        return $tab;
    })->all();
@endphp
<nav class="tp-bottom-nav rs-bottom-nav{{ count($bnTabs) >= 5 ? ' tp-bottom-nav--six' : (count($bnTabs) === 4 ? ' tp-bottom-nav--five' : '') }}" aria-label="{{ $bnLabel }}">
    @foreach($bnTabs as $tab)
        @php $bnUnread = ! empty($tab['badge']) ? \App\Support\Messaging\MessagingBadge::total() : 0; @endphp
        <a href="{{ route($tab['route']) }}" class="tp-bottom-nav__item{{ $tab['active'] ? ' is-active' : '' }}"@if($tab['active']) aria-current="page"@endif
            @if(! empty($tab['badge'])) aria-label="Messages{{ $bnUnread ? ', '.$bnUnread.' unread' : '' }}" @endif>
            @if(! empty($tab['badge']))
                <span class="position-relative d-inline-block">
                    <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                    <span class="tp-bottom-nav__dot" data-msg-bottom-dot @if(! $bnUnread) hidden @endif></span>
                </span>
            @else
                <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
            @endif
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
    <button type="button" class="tp-bottom-nav__item tp-bottom-nav__more js-more-toggle{{ $bnAnyActive ? '' : ' is-active' }}"
        aria-controls="menu" aria-expanded="false" aria-label="More: open the full menu">
        <i class="bi bi-three-dots" aria-hidden="true"></i>
        <span>More</span>
    </button>
</nav>
