@php
    $firstPhoto = null;
    $photoIds = array_values(array_filter(array_map('trim', explode(',', (string) $property->photos))));
    if ($photoIds) {
        $firstPhoto = uploaded_asset($photoIds[0]);
    }
    $address = trim(implode(', ', array_filter([
        $property->line_1,
        $property->line_2,
        $property->city,
        $property->postcode,
    ])));
    $priceLabel = '';
    if (!empty($property->letting_price)) {
        $priceLabel = '£' . number_format((float) $property->letting_price, 0) . '/mo';
    } elseif (!empty($property->price)) {
        $priceLabel = '£' . number_format((float) $property->price, 0);
    }
@endphp
<div class="pcc-detail-header" id="pccDetailHeader">
    <div class="pcc-detail-header-inner">
        <div class="pcc-detail-thumb">
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
            @if($property->prop_ref_no)
                <div class="pcc-detail-ref">{{ $property->prop_ref_no }}</div>
            @endif
            <h5 class="pcc-detail-name">{{ $property->prop_name ?: $property->line_1 }}</h5>
            <div class="pcc-detail-address">{{ $address }}</div>
            <div class="pcc-detail-meta">
                @if($property->property_type)
                    <span>{{ $property->property_type }}</span>
                @endif
                @if($property->sales_current_status)
                    <span class="pcc-status">{{ $property->sales_current_status }}</span>
                @endif
                @if($property->letting_current_status)
                    <span class="pcc-status">{{ $property->letting_current_status }}</span>
                @endif
                @if($priceLabel)
                    <span class="pcc-detail-price">{{ $priceLabel }}</span>
                @endif
            </div>
        </div>
        @include('backend.properties.partials.detail-actions', ['property' => $property])
    </div>
</div>
