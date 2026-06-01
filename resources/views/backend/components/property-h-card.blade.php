<div class="pv_content_wrapper {{ $cardStyle == 'vertical'? 'vertical_card' : '' }} {{$class}}" data-property-id="{{ $propertyId }}">
    <div class="pv_image">
        <img src="{{ asset('/asset/images/temp-property.webp') }}" alt="property">
    </div>

    <div class="pv_content">
        <div class="pvc_poperty_name">
            @if($propertyName)
                {{ $propertyName }}
            @else
                <em>Property name not available</em>
            @endif
        </div>

        <div class="rs_property_icons">
            <div class="bed_icon rs_tooltip" data-label="bedroom">
                <img src="{{ asset('asset/images/svg/icons/bed.svg') }}" alt="bedroom">
                @if($bed)
                    {{ $bed }}
                @else
                    N/A
                @endif
            </div>

            <div class="bath_icon rs_tooltip" data-label="bathroom">
                <img src="{{ asset('asset/images/svg/icons/bath.svg') }}" alt="bathroom">
                @if($bath)
                    {{ $bath }}
                @else
                    N/A
                @endif
            </div>

            <div class="floors_icon rs_tooltip" data-label="Floors">
                <img src="{{ asset('asset/images/svg/icons/floor.svg') }}" alt="Floors">
                @if($floor)
                    {{ $floor }}
                @else
                    N/A
                @endif
            </div>

            <div class="living_icon rs_tooltip" data-label="Sofa">
                <img src="{{ asset('asset/images/svg/icons/sofa.svg') }}" alt="sofa">
                @if($living)
                    {{ $living }}
                @else
                    N/A
                @endif
            </div>
        </div>

        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_type">
                    Type: 
                    <strong>
                        @if($type)
                            {{ $type }}
                        @else
                            Unknown
                        @endif
                    </strong>
                </div>
            </div>

            <div class="rs_col">
                <div class="pv_availability">
                    Availability:
                    <strong>
                        @if($available)
                            {{ $available }}
                        @else
                            Not specified
                        @endif
                    </strong>
                </div>
            </div>
        </div>
        @if($type == 'sales' || $type == 'both')
        <div class="pvc_price">
            Price: 
            <span>
                @if($price)
                    £{{ $price }}
                @else
                    N/A
                @endif
            </span>
        </div>
        @endif
        @if($type == 'lettings' || $type == 'both')
        <div class="pvc_price">
            Letting Price: 
            <span>
                @if(isset($lettingPrice) && $lettingPrice)
                    £{{ $lettingPrice }} 
                    <br>
                    <small>Weekly: £{{ $weeklyLettingPrice }}</small>
                @else
                    N/A
                @endif
            </span>
        </div>
        @endif
    </div>
</div>
