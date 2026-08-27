<div class="pcc-detail-header" id="pccDetailHeader">
    <div class="pcc-detail-header-inner">
        <div class="pcc-detail-thumb">
            @php
                $firstPhoto = null;
                $photoIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->photos))));
                if ($photoIds) {
                    $firstPhoto = uploaded_asset($photoIds[0]);
                }
            @endphp
            @if($firstPhoto)
                <img src="{{ $firstPhoto }}" alt="Property" class="pcc-thumb-img"
                     onclick="openImageModal('{{ $firstPhoto }}')">
            @else
                <div class="pcc-thumb-placeholder">
                    <i class="bi bi-building"></i>
                </div>
            @endif
        </div>
        <div class="pcc-detail-info">
            <div class="pcc-detail-ref">Property Ref: <strong>{{ $property->prop_ref_no }}</strong></div>
            <h5 class="pcc-detail-name">{{ $property->prop_name ?: $property->line_1 }}</h5>
            <div class="pcc-detail-address">
                {{ $property->line_1 }}{{ $property->line_2 ? ', '.$property->line_2 : '' }},
                {{ $property->city }}, {{ $property->postcode }}
            </div>
            <div class="pcc-detail-badges">
                <span class="badge bg-light text-dark border">{{ $property->property_type }}</span>
                @if($property->sales_current_status)
                    <span class="badge {{ $property->sales_current_status == 'available' || $property->sales_current_status == 'for sale' ? 'bg-success' : ($property->sales_current_status == 'let agreed' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                        {{ $property->sales_current_status }}
                    </span>
                @endif
                @if($property->letting_current_status)
                    <span class="badge {{ $property->letting_current_status == 'available' ? 'bg-success' : ($property->letting_current_status == 'let agreed' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                        {{ $property->letting_current_status }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
