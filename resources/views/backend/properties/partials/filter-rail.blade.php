@php
    $pccFiltersExpanded = request()->hasAny(['status','property_type','manager_id','branch_id','compliance_expiring','has_open_repairs']);
@endphp
<div class="pcc-filter-rail {{ $pccFiltersExpanded ? 'is-open' : '' }}" id="pccFilterRail">
    <div class="pcc-filter-rail-header">
        <span class="pcc-filter-rail-title">Filters</span>
        <div class="pcc-filter-rail-tools">
            <button class="pcc-filter-drawer-btn" id="pccFilterDrawerBtn" type="button" aria-expanded="{{ $pccFiltersExpanded ? 'true' : 'false' }}">
                <i class="bi bi-sliders"></i>
                <span>Filters</span>
            </button>
            <button class="pcc-filter-rail-reset" id="pccFilterReset" title="Clear all filters" type="button">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>
    </div>

    <div class="pcc-filter-search">
        <input type="search" name="search" id="pccListSearch" class="form-control form-control-sm"
               placeholder="Search address, ref, postcode..."
               value="{{ request('search') }}" autocomplete="off">
    </div>
    <div class="pcc-filter-groups" id="pccFilterGroups">

    @php
        $statuses = ['for sale','let agreed','available','sold','not available'];
        $activeStatuses = collect(is_array(request('status')) ? request('status') : explode(',', (string) request('status')))
            ->map(fn ($s) => trim((string) $s))
            ->filter();
        $statusFiltersOpen = $activeStatuses->isNotEmpty();
    @endphp
    <div class="pcc-filter-section">
        <button class="pcc-filter-section-toggle {{ $statusFiltersOpen ? 'open' : '' }}" data-section="status" type="button">
            <span>Status</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="pcc-filter-section-body {{ $statusFiltersOpen ? 'open' : '' }}" id="pccFilterStatus">
            @foreach($statuses as $status)
                <label class="pcc-filter-check">
                    <input type="checkbox" name="status" value="{{ $status }}"
                        {{ $activeStatuses->contains($status) ? 'checked' : '' }}>
                    <span>{{ ucwords(str_replace('-',' ',$status)) }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="pcc-filter-section">
        <button class="pcc-filter-section-toggle" data-section="type" type="button">
            <span>Property Type</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="pcc-filter-section-body" id="pccFilterType">
            @foreach(['sales'=>'Sales','lettings'=>'Lettings','both'=>'Both'] as $val=>$label)
                <label class="pcc-filter-radio">
                    <input type="radio" name="property_type" value="{{ $val }}"
                        {{ request('property_type') == $val ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="pcc-filter-section">
        <button class="pcc-filter-section-toggle" data-section="compliance" type="button">
            <span>Quick Filters</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="pcc-filter-section-body" id="pccFilterCompliance">
            <label class="pcc-filter-check">
                <input type="checkbox" name="compliance_expiring" value="1"
                    {{ request('compliance_expiring') ? 'checked' : '' }}>
                <span>Compliance expiring soon</span>
            </label>
            <label class="pcc-filter-check">
                <input type="checkbox" name="has_open_repairs" value="1"
                    {{ request('has_open_repairs') ? 'checked' : '' }}>
                <span>Has open repairs</span>
            </label>
        </div>
    </div>
    </div>

    @if(request()->hasAny(['status','property_type','manager_id','branch_id','compliance_expiring','has_open_repairs','search']))
        <div class="pcc-active-filters" id="pccActiveFilters">
            <span class="pcc-active-filters-label">Active:</span>
            @foreach(request()->only(['status','property_type','manager_id','branch_id','compliance_expiring','has_open_repairs','search']) as $key=>$val)
                @if($val && $val !== '' && $val !== [] && $val !== [0])
                    <span class="pcc-filter-chip" data-key="{{ $key }}">
                        {{ is_array($val) ? implode(',', $val) : $val }}
                        <button class="pcc-filter-chip-remove" data-key="{{ $key }}" type="button">&times;</button>
                    </span>
                @endif
            @endforeach
        </div>
    @endif
</div>
