@props(['tabs', 'class' => '', 'activeTab' => null])

@php
    $activeIndex = $activeTab !== null ? (int) $activeTab : 0;
    $tabIcons = [
        'property' => 'bi bi-info-circle',
        'owners' => 'bi bi-people',
        'compliance' => 'bi bi-shield-check',
        'media' => 'bi bi-images',
        'offers' => 'bi bi-handshake',
        'tenancy' => 'bi bi-house-door',
        'apd' => 'bi bi-calendar-check',
        'teams' => 'bi bi-person-badge',
        'documents' => 'bi bi-file-earmark-text',
        'notes' => 'bi bi-journal-text',
        'appointments' => 'bi bi-calendar-event',
        'statement' => 'bi bi-receipt',
        'responsibility' => 'bi bi-diagram-3',
    ];
@endphp

<div class="pv_tabs {{ $class }}">
    <div class="property-tabs-scroll">
        <ul class="nav nav-pills property-nav-pills">
            @foreach ($tabs as $key => $tab)
                @php
                    $tabName = strtolower($tab['name']);
                    $icon = $tabIcons[$tabName] ?? 'bi bi-circle';
                    $isActive = $key === $activeIndex;
                @endphp
                <li class="nav-item">
                    <a href="#{{ Str::slug($tab['name']) }}"
                       data-tab-name="{{ $tabName }}"
                       class="nav-link tab-link {{ $isActive ? 'active' : '' }}">
                        <i class="{{ $icon }} me-1"></i>
                        <span class="tab-label">{{ $tab['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
