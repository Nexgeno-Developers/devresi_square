@props([
    'class' => '',
    'propertyName' => '',
    'bed' => '',
    'bath' => '',
    'floor' => '',
    'living' => '',
    'type' => '',
    'available' => '',
    'price' => '',
    'lettingPrice' => '',
    'cardStyle' => '',
    'propertyId' => ''
])

<div class="pv_content_wrapper {{ $cardStyle == 'vertical'? 'vertical_card' : '' }} {{$class}}" data-property-id="{{ $propertyId }}">
    <div class="pv_content">
        <div class="pvc_property_name">
            @if($propertyName)
                {{ $propertyName }}
            @else
                <em>Property address not available</em>
            @endif
        </div>

        <div class="property_details mt-2">
            @if($bed || $bath || $floor || $living)
                <div class="property_features d-flex gap-2 mb-2">
                    @if($bed)
                        <span><i class="bi bi-door-closed"></i> {{ $bed }} Bed</span>
                    @endif
                    @if($bath)
                        <span><i class="bi bi-droplet"></i> {{ $bath }} Bath</span>
                    @endif
                    @if($floor)
                        <span><i class="bi bi-layers"></i> {{ $floor }} Floor</span>
                    @endif
                    @if($living)
                        <span><i class="bi bi-house"></i> {{ $living }} Living</span>
                    @endif
                </div>
            @endif

            <div class="property_info">
                @if($type)
                    <div class="property_type">
                        <strong>Type:</strong> {{ $type }}
                    </div>
                @endif
                @if($available)
                    <div class="property_available">
                        <strong>Available:</strong> {{ $available }}
                    </div>
                @endif
                @if($price)
                    <div class="property_price">
                        <strong>Price:</strong> £{{ number_format($price, 2) }}
                    </div>
                @endif
                @if($lettingPrice)
                    <div class="property_letting_price">
                        <strong>Letting Price:</strong> £{{ number_format($lettingPrice, 2) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
