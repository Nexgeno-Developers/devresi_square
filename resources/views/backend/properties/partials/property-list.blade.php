@foreach ($properties as $property)
    @php
        $addressParts = array_filter([
            $property['prop_name'],
            $property['line_1'],
            $property['line_2'],
            $property['city'],
            $property['country'],
            $property['postcode'],
        ]);
        $fullAddress = implode(', ', $addressParts);
    @endphp
    <x-backend.property-card 
        class="property-card" 
        propertyName="{{ $fullAddress }}" 
        bed="{{ $property['bedroom'] }}" 
        bath="{{ $property['bathroom'] }}" 
        floor="{{ $property['floor'] }}" 
        living="{{ $property['reception'] }}" 
        type="{{ $property['property_type'] }}" 
        available="{{ $property['available_from'] }}" 
        price="{{ $property['price'] }}" 
        lettingPrice="{{ $property['letting_price'] ?? '' }}" 
        cardStyle="" 
        propertyId="{{ $property['id'] }}" 
    />
@endforeach

@if($properties->hasPages())
    <div class="pagination-wrapper p-3">
        {{ $properties->links() }}
    </div>
@endif
