<div class="pv_content_wrapper {{ $cardStyle == 'vertical'? 'vertical_card' : '' }} {{$class}}" data-property-id="{{ $propertyId }}">
    <div class="pv_image">
        <img src="{{ asset('/asset/images/temp-property.webp') }}" alt="property">
    </div>
    <div class="pv_content">
        <div class="pvc_poperty_name">{{ $propertyName }}</div>
        <div class="rs_property_icons">
            @if($bed)
                <div class="bed_icon rs_tooltip">
                    <img src=" {{asset('asset/images/svg/icons/bed.svg')}} " alt="bedroom" > {{ $bed }}
                </div>
            @endif
            @if($bath)
            <div class="bath_icon rs_tooltip">
                <img src=" {{asset('asset/images/svg/icons/bath.svg')}} " alt="bathroom"> {{ $bath }}
            </div>
            @endif
            @if($floor)
                <div class="floors_icon rs_tooltip">
                    <img src=" {{asset('asset/images/svg/icons/floor.svg')}} " alt="Floors"> {{ $floor }}
                </div>
            @endif
            @if($living)
                <div class="living_icon rs_tooltip">
                    <img src=" {{asset('asset/images/svg/icons/sofa.svg')}} " alt="sofa"> {{ $living }}
                </div>
            @endif
        </div>
        <div class="rs_row">
            @if($type)
                <div class="rs_col">
                    <div class="pv_type">Type: <strong> {{ $type }}</strong></div>
                </div>
            @endif
            @if($available)
                <div class="rs_col">
                    <div class="pv_availability">Availability: <strong>{{ $available }}</strong></div>
                </div>
            @endif
        </div>
        {{-- rs_row end  --}}
        @if($price)
            <div class="pvc_price">
                Price: <span>£{{ $price }}</span>
            </div>
        @endif
    </div>
    {{-- pv_content end  --}}
</div>
