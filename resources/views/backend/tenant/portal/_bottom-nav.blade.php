@php
    $tpMessaging = \App\Support\Messaging\MessagingBadge::current();
    $tpTabs = [
        ['label' => 'Home', 'route' => 'backend.home', 'match' => ['backend.home'], 'icon' => 'bi-house'],
        ['label' => 'Rent', 'route' => 'tenant.rent', 'match' => ['tenant.rent', 'tenant.rent.*'], 'icon' => 'bi-cash-stack'],
        ['label' => 'Repairs', 'route' => 'tenant.maintenance', 'match' => ['tenant.maintenance', 'admin.property_repairs.create'], 'icon' => 'bi-tools'],
        ['label' => 'Messages', 'route' => 'portal.messages.index', 'match' => ['portal.messages.*'], 'icon' => 'bi-chat-dots', 'badge' => true],
        ['label' => 'Docs', 'route' => 'tenant.documents', 'match' => ['tenant.documents'], 'icon' => 'bi-folder'],
        ['label' => 'Me', 'route' => 'tenant.profile', 'match' => ['tenant.profile', 'tenant.notifications', 'tenant.tenancy', 'tenant.calendar'], 'icon' => 'bi-person'],
    ];
    if (! $tpMessaging) {
        $tpTabs = array_values(array_filter($tpTabs, fn ($tab) => empty($tab['badge'])));
    }
@endphp
<nav class="tp-bottom-nav{{ count($tpTabs) > 5 ? ' tp-bottom-nav--six' : '' }}" aria-label="Tenant phone navigation">
    @foreach($tpTabs as $tab)
        @php
            $active = false;
            foreach ($tab['match'] as $pattern) {
                if (request()->routeIs($pattern)) {
                    $active = true;
                    break;
                }
            }
            $unread = ! empty($tab['badge']) ? \App\Support\Messaging\MessagingBadge::total() : 0;
        @endphp
        <a href="{{ route($tab['route']) }}" class="tp-bottom-nav__item{{ $active ? ' is-active' : '' }}"
            @if(! empty($tab['badge'])) aria-label="Messages{{ $unread ? ', '.$unread.' unread' : '' }}" @endif>
            <span class="position-relative d-inline-block">
                <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                @if(! empty($tab['badge']))
                    <span class="tp-bottom-nav__dot" data-msg-bottom-dot @if(! $unread) hidden @endif></span>
                @endif
            </span>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
