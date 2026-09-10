@php
    $selectedId = (int) ($propertyId ?? 0);
    $isPortalUser = $isPortalUser ?? false;
    $showBulk = ! $isPortalUser && auth()->user()?->can('delete properties');
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

            $metaBits = [];
            if (!empty($listProperty['bedroom'])) {
                $metaBits[] = $listProperty['bedroom'] . ' bed';
            }
            if (!empty($listProperty['bathroom'])) {
                $metaBits[] = $listProperty['bathroom'] . ' bath';
            }
            if (!empty($listProperty['property_type']) && ! is_landlord_plan_user()) {
                $metaBits[] = $listProperty['property_type'];
            }

            $priceLabel = '';
            if (!empty($listProperty['letting_price'])) {
                $priceLabel = '£' . number_format((float) $listProperty['letting_price'], 0) . '/mo';
            } elseif (!empty($listProperty['price'])) {
                $priceLabel = '£' . number_format((float) $listProperty['price'], 0);
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
                    @if($priceLabel)
                        <span class="pcc-hcard-price">{{ $priceLabel }}</span>
                    @endif
                </div>
                <div class="pcc-hcard-sub" title="{{ $fullAddress }}">{{ $fullAddress }}</div>
                <div class="pcc-hcard-meta">
                    @if($displayStatus)
                        <span class="pcc-status" data-status="{{ strtolower($displayStatus) }}">{{ $displayStatus }}</span>
                    @endif
                    @if($metaBits)
                        <span>{{ implode(' · ', $metaBits) }}</span>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
@elseif(request()->hasAny(['search', 'property_type', 'status', 'compliance_expiring', 'has_open_repairs']))
    <div class="pcc-empty-list">
        <h5>No properties found</h5>
        <p>Try adjusting your search or filters.</p>
        <a href="{{ route('admin.properties.index') }}" class="pcc-btn-ghost">Clear filters</a>
    </div>
@else
    <div class="pcc-empty-list">
        <h5>No properties yet</h5>
        <p>Get started by adding your first property.</p>
        @can('create properties')
            <a href="{{ property_create_url() }}" class="pcc-btn-ink">Add property</a>
        @endcan
    </div>
@endif
