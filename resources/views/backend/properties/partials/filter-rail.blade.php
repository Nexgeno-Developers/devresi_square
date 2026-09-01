@php
    $activeType = (string) request('property_type');
    $statuses = ['for sale','let agreed','available','sold','not available'];
    $activeStatuses = collect(is_array(request('status')) ? request('status') : explode(',', (string) request('status')))
        ->map(fn ($s) => trim((string) $s))
        ->filter();
@endphp
<div class="pcc-filter-rail" id="pccFilterRail">
    <div class="pcc-view-chips" role="tablist" aria-label="Property type">
        <label class="pcc-chip {{ $activeType === '' ? 'is-on' : '' }}">
            <input type="radio" name="property_type" value="" {{ $activeType === '' ? 'checked' : '' }}>
            All
        </label>
        <label class="pcc-chip {{ $activeType === 'lettings' ? 'is-on' : '' }}">
            <input type="radio" name="property_type" value="lettings" {{ $activeType === 'lettings' ? 'checked' : '' }}>
            Lettings
        </label>
        <label class="pcc-chip {{ $activeType === 'sales' ? 'is-on' : '' }}">
            <input type="radio" name="property_type" value="sales" {{ $activeType === 'sales' ? 'checked' : '' }}>
            Sales
        </label>
        <label class="pcc-chip {{ $activeType === 'both' ? 'is-on' : '' }}">
            <input type="radio" name="property_type" value="both" {{ $activeType === 'both' ? 'checked' : '' }}>
            Both
        </label>
    </div>

    <div class="pcc-filter-extras">
        <select name="status" id="pccFilterStatusSelect" class="pcc-quiet-select" aria-label="Status">
            <option value="">All statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" {{ $activeStatuses->count() === 1 && $activeStatuses->first() === $status ? 'selected' : '' }}>
                    {{ ucwords($status) }}
                </option>
            @endforeach
        </select>

        <label class="pcc-chip pcc-chip-check {{ request('compliance_expiring') ? 'is-on' : '' }}">
            <input type="checkbox" name="compliance_expiring" value="1" {{ request('compliance_expiring') ? 'checked' : '' }}>
            Expiring compliance
        </label>
        <label class="pcc-chip pcc-chip-check {{ request('has_open_repairs') ? 'is-on' : '' }}">
            <input type="checkbox" name="has_open_repairs" value="1" {{ request('has_open_repairs') ? 'checked' : '' }}>
            Open repairs
        </label>

        @if(Route::has('admin.properties.soft_deleted'))
            <a href="{{ route('admin.properties.soft_deleted') }}" class="pcc-chip-link">Deleted</a>
        @endif

        <button class="pcc-filter-rail-reset" id="pccFilterReset" title="Clear filters" type="button">
            Reset
        </button>
    </div>
</div>
