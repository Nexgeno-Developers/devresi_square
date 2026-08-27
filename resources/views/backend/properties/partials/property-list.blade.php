@php
    $selectedId = (int) ($propertyId ?? 0);
    $isPortalUser = $isPortalUser ?? false;
    $showBulk = ! $isPortalUser && auth()->user()?->can('delete properties');

    $statusBadgeClass = function ($status) {
        return match (strtolower((string) $status)) {
            'for sale', 'available' => 'bg-success',
            'on hold', 'under offer', 'let agreed' => 'bg-warning text-dark',
            'sold', 'sold stc', 'sold by other', 'exchanged', 'let by other' => 'bg-danger',
            'not available' => 'bg-secondary',
            default => 'bg-light text-dark border',
        };
    };
@endphp

@if($properties->count() > 0)
    @foreach ($properties as $listProperty)
        @php
            $addressParts = array_filter([
                $listProperty['line_1'],
                $listProperty['line_2'],
                $listProperty['city'],
                $listProperty['postcode'],
            ]);
            $fullAddress = implode(', ', $addressParts);
            $title = $listProperty['prop_name'] ?: ($listProperty['line_1'] ?: 'Property address not available');

            $salesStatus = $listProperty['sales_current_status'] ?? '';
            $lettingStatus = $listProperty['letting_current_status'] ?? '';
            $displayStatus = $salesStatus ?: $lettingStatus;

            $photoIds = array_values(array_filter(array_map('trim', explode(',', (string) ($listProperty['photos'] ?? '')))));
            $thumbUrl = $photoIds ? uploaded_asset($photoIds[0]) : '';

            $priceBits = [];
            if (!empty($listProperty['bedroom'])) {
                $priceBits[] = $listProperty['bedroom'] . ' Bed';
            }
            if (!empty($listProperty['bathroom'])) {
                $priceBits[] = $listProperty['bathroom'] . ' Bath';
            }
            if (!empty($listProperty['property_type'])) {
                $priceBits[] = $listProperty['property_type'];
            }
            if (!empty($listProperty['letting_price'])) {
                $priceBits[] = '£' . number_format((float) $listProperty['letting_price'], 0) . '/mo';
            } elseif (!empty($listProperty['price'])) {
                $priceBits[] = '£' . number_format((float) $listProperty['price'], 0);
            }
        @endphp
        <article class="pcc-hcard property-card {{ $selectedId === (int) $listProperty['id'] ? 'current' : '' }}"
             data-property-id="{{ $listProperty['id'] }}"
             data-important-note="{{ e($listProperty['imp_notes'] ?? '') }}">
            @if($showBulk)
                <label class="pcc-hcard-check" onclick="event.stopPropagation();">
                    <input type="checkbox"
                           class="pcc-bulk-checkbox"
                           name="pcc_bulk_select[]"
                           value="{{ $listProperty['id'] }}"
                           data-id="{{ $listProperty['id'] }}"
                           title="Select for bulk action">
                </label>
            @endif
            <div class="pcc-hcard-thumb" aria-hidden="true">
                @if($thumbUrl)
                    <img src="{{ $thumbUrl }}" alt="">
                @else
                    <i class="bi bi-building"></i>
                @endif
            </div>
            <div class="pcc-hcard-main">
                <div class="pcc-hcard-row">
                    <div class="pcc-hcard-name" title="{{ $fullAddress }}">{{ $title }}</div>
                    @if($displayStatus)
                        <span class="badge {{ $statusBadgeClass($displayStatus) }}">{{ $displayStatus }}</span>
                    @endif
                </div>
                <div class="pcc-hcard-sub" title="{{ $fullAddress }}">{{ $fullAddress }}</div>
                @if($priceBits)
                    <div class="pcc-hcard-meta">{{ implode(' · ', $priceBits) }}</div>
                @endif
            </div>
        </article>
    @endforeach
@elseif(request()->hasAny(['search', 'property_type', 'status', 'compliance_expiring', 'has_open_repairs']))
    <div class="text-center py-5">
        <i class="bi bi-search display-4 text-muted"></i>
        <h5 class="mt-3 text-muted">No properties found</h5>
        <p class="text-muted">Try adjusting your search or filters.</p>
        <a href="{{ route('admin.properties.index') }}" class="btn btn-outline-primary btn-sm">Clear filters</a>
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-building display-4 text-muted"></i>
        <h5 class="mt-3 text-muted">No properties yet</h5>
        <p class="text-muted">Get started by adding your first property.</p>
        @can('create properties')
            <a href="{{ route('admin.properties.quick') }}" class="btn btn-primary btn-sm">Add First Property</a>
        @endcan
    </div>
@endif
