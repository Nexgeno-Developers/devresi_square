<div class="card mb-4">
    <div class="card-header">
        <strong>Linked Properties</strong>
    </div>
    <div class="card-body">
        @if($properties->isEmpty())
            <p class="text-muted">No properties linked to this user.</p>
        @else
            <ul class="list-group">
                @foreach($properties as $property)
                    @php
                        $hasPropertyName = filled($property->prop_name);
                        $propertyName = trim((string) ($property->prop_name ?: $property->line_1))
                            ?: 'Property ' . ($property->prop_ref_no ?: '#' . $property->id);
                        $countryName = $property->countryRelation?->name
                            ?: (is_numeric($property->country) ? null : $property->country);
                        $addressParts = array_filter([
                            $hasPropertyName ? $property->line_1 : null,
                            $property->line_2,
                            $property->city,
                            $property->county,
                            $property->postcode,
                            $countryName,
                        ], fn ($part) => filled($part));
                        $propertyAddress = implode(', ', array_unique($addressParts));
                        $propertyUrl = route('admin.properties.index', [
                            'property_id' => $property->id,
                            'tabname' => 'Property',
                        ]);
                    @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <a href="{{ $propertyUrl }}" class="fw-semibold text-decoration-none">
                                {{ $propertyName }}
                            </a>
                            @if($propertyAddress)
                                <div class="text-muted small mt-1">{{ $propertyAddress }}</div>
                            @endif
                            <div class="text-muted small mt-1">
                                Reference: {{ $property->prop_ref_no ?: 'N/A' }}
                                @if($property->specific_property_type)
                                    &middot; {{ $property->specific_property_type }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ $propertyUrl }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                            View details
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Linked Tenancies</strong>
    </div>
    <div class="card-body">
        @if($user->tenancies->isEmpty())
            <p class="text-muted">No tenancies linked to this user.</p>
        @else
            <ul class="list-group">
                @foreach($user->tenancies as $tenancy)
                    <li class="list-group-item">
                        {{ $tenancy->title ?? 'Unnamed Tenancy' }}
                        <span class="text-muted">(#{{ $tenancy->id }})</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
