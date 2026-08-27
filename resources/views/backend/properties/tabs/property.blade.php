@php
    $addressParts = [];
    if (isset($property) && isset($property->line_1)) { $addressParts[] = $property->line_1; }
    if (isset($property) && isset($property->line_2)) { $addressParts[] = $property->line_2; }
    if (isset($property) && isset($property->city)) { $addressParts[] = $property->city; }
    if (isset($property) && isset($property->country)) { $addressParts[] = $property->country; }
    if (isset($property) && isset($property->postcode)) { $addressParts[] = $property->postcode; }
    $address = implode(', ', $addressParts);

    $propertyType = $property->property_type ?? '';
    $transactionType = $property->transaction_type ?? '';
    $specificPropertyType = $property->specific_property_type ?? '';
    $bedroom = $property->bedroom ?? '';
    $bathroom = $property->bathroom ?? '';
    $reception = $property->reception ?? '';
    $floor = $property->floor ?? '';
    $squareFeet = $property->square_feet ?? '';
    $squareMeter = $property->square_meter ?? '';
    $parking = booleanToYesNo($property->parking) ?? '';
    $parkingLocation = $property->parking_location ?? '';
    $garden = booleanToYesNo($property->garden) ?? '';
    $balcony = booleanToYesNo($property->balcony) ?? '';
    $aspects = $property->aspects ?? '';
    $petsAllowed = booleanToYesNo($property->pets_allow) ?? '';
    $service = $property->service ?? '';
    $collectingRent = booleanToYesNo($property->collecting_rent) ?? '';
    $availableFrom = formatDate($property->available_from) ?? '';
    $salesStatus = $property->sales_current_status ?? '';
    $lettingStatus = $property->letting_current_status ?? '';
    $salesDescription = $property->sales_status_description ?? '';
    $lettingDescription = $property->letting_status_description ?? '';
    $price = $property->price ?? '';
    $lettingPrice = $property->letting_price ?? '';
    $groundRent = $property->ground_rent ?? '';
    $serviceCharge = $property->service_charge ?? '';
    $annualCouncilTax = $property->annual_council_tax ?? '';
    $councilTaxBand = $property->council_tax_band ?? '';
    $estateCharge = $property->estate_charge ?? '';
    $miscellaneousCharge = $property->miscellaneous_charge ?? '';
    $tenure = $property->tenure ?? '';
    $lengthOfLease = $property->length_of_lease ?? '';
    $epcRequired = booleanToYesNo($property->epc_required) ?? '';
    $epcRating = $property->epc_rating ?? '';
    $isGas = booleanToYesNo($property->is_gas) ?? '';
    $marketOn = $property->market_on ?? '';
    $impNotes = $property->imp_notes ?? '';
    $photos = $property->photos ?? '';
    $floorPlan = $property->floor_plan ?? '';
    $view360 = $property->view_360 ?? '';
    $youtubeUrl = $property->youtube_url ?? '';
    $instagramUrl = $property->instagram_url ?? '';
    $accessArrangement = $property->access_arrangement ?? '';
    $keyHighlights = $property->key_highlights ?? '';
    $usefulInformation = $property->useful_information ?? '';
@endphp

<div class="property-overview-dashboard">
    {{-- Row 1: Key Facts Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-building"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Property Type</div>
                    <div class="fact-value text-capitalize">{{ $propertyType ?: 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-tag"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Category</div>
                    <div class="fact-value text-capitalize">{{ $transactionType ?: 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-door-closed"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Bedrooms</div>
                    <div class="fact-value">{{ $bedroom ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-droplet"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Bathrooms</div>
                    <div class="fact-value">{{ $bathroom ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-sofa"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Reception</div>
                    <div class="fact-value">{{ $reception ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: Two Column Layout --}}
    <div class="row g-3 mb-4">
        {{-- Left Column: Description & Note --}}
        <div class="col-lg-6">
            {{-- Description Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Description</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_description" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="description-section">
                        @if($salesDescription)
                            <div class="mb-2">
                                <strong>Sales:</strong>
                                <x-toggle-description :text="$salesDescription" :limit="180" />
                            </div>
                        @endif
                        @if($lettingDescription)
                            <div>
                                <strong>Lettings:</strong>
                                <x-toggle-description :text="$lettingDescription" :limit="180" />
                            </div>
                        @endif
                        @if(!$salesDescription && !$lettingDescription)
                            <span class="text-muted">No description added.</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Important Note Card --}}
            @canany(['edit important note', 'view important note'])
            <div class="card mb-3 border-warning">
                <div class="card-header d-flex justify-content-between align-items-center bg-warning bg-opacity-10">
                    <span class="card-title mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Important Note</span>
                    @can('edit important note')
                    <button class="btn btn-sm btn-outline-warning editForm" data-form="notes" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="notes-update-ajax" id="section-notes-{{ $property->id }}">
                        @include("backend.properties.popup_forms.notes", ['property' => $property, 'editMode' => false])
                    </div>
                </div>
            </div>
            @endcanany
        </div>

        {{-- Right Column: Status & Availability --}}
        <div class="col-lg-6">
            {{-- Status Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Status & Availability</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_status" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="status-item">
                                <span class="status-label">Available From:</span>
                                <span class="status-value">{{ $availableFrom ?: 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="status-item">
                                <span class="status-label">Tenure:</span>
                                <span class="status-value text-capitalize">{{ $tenure ?: 'N/A' }}</span>
                            </div>
                        </div>
                        @if($lengthOfLease)
                        <div class="col-md-6">
                            <div class="status-item">
                                <span class="status-label">Length of Lease:</span>
                                <span class="status-value">{{ $lengthOfLease }} years</span>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="status-item">
                                <span class="status-label">Local Authority:</span>
                                <span class="status-value">{{ $property->localAuthority->display_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Pricing</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="availability_pricing" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @if($price)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Sales Price:</span>
                                <span class="pricing-value">£{{ number_format($price, 2) }}</span>
                            </div>
                        </div>
                        @endif
                        @if($lettingPrice)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Letting Price:</span>
                                <span class="pricing-value">£{{ number_format($lettingPrice, 2) }}/mo</span>
                            </div>
                        </div>
                        @endif
                        @if($groundRent)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Ground Rent:</span>
                                <span class="pricing-value">£{{ number_format($groundRent, 2) }}/yr</span>
                            </div>
                        </div>
                        @endif
                        @if($serviceCharge)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Service Charge:</span>
                                <span class="pricing-value">£{{ number_format($serviceCharge, 2) }}/yr</span>
                            </div>
                        </div>
                        @endif
                        @if($estateCharge)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Estate Charge:</span>
                                <span class="pricing-value">£{{ number_format($estateCharge, 2) }}</span>
                            </div>
                        </div>
                        @endif
                        @if($miscellaneousCharge)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Misc. Charge:</span>
                                <span class="pricing-value">£{{ number_format($miscellaneousCharge, 2) }}/yr</span>
                            </div>
                        </div>
                        @endif
                        @if($annualCouncilTax)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Council Tax:</span>
                                <span class="pricing-value">£{{ number_format($annualCouncilTax, 2) }}/yr</span>
                            </div>
                        </div>
                        @endif
                        @if($councilTaxBand)
                        <div class="col-md-6">
                            <div class="pricing-item">
                                <span class="pricing-label">Council Tax Band:</span>
                                <span class="pricing-value">{{ $councilTaxBand }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Features & Services --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            {{-- Property Features Card --}}
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Features</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_features" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-label">Bedrooms:</span>
                            <span class="feature-value">{{ $bedroom ?: '-' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Bathrooms:</span>
                            <span class="feature-value">{{ $bathroom ?: '-' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Reception:</span>
                            <span class="feature-value">{{ $reception ?: '-' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Floor:</span>
                            <span class="feature-value text-capitalize">{{ $floor ?: '-' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Balcony:</span>
                            <span class="feature-value">{{ $balcony }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Garden:</span>
                            <span class="feature-value">{{ $garden }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Parking:</span>
                            <span class="feature-value">{{ $parking }}</span>
                        </div>
                        @if($parking == 'Yes' && $parkingLocation)
                        <div class="feature-item">
                            <span class="feature-label">Parking Location:</span>
                            <span class="feature-value">{{ $parkingLocation }}</span>
                        </div>
                        @endif
                        <div class="feature-item">
                            <span class="feature-label">Aspect:</span>
                            <span class="feature-value text-capitalize">{{ $aspects ?: '-' }}</span>
                        </div>
                        @if($squareFeet)
                        <div class="feature-item">
                            <span class="feature-label">Area:</span>
                            <span class="feature-value">{{ number_format($squareFeet, 2) }} sqft</span>
                        </div>
                        @endif
                        @if($squareMeter)
                        <div class="feature-item">
                            <span class="feature-label">Area:</span>
                            <span class="feature-value">{{ number_format($squareMeter, 2) }} sqm</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Services Card --}}
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Services</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_services" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-label">Service:</span>
                            <span class="feature-value">{{ $service ?: 'N/A' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Pets Allowed:</span>
                            <span class="feature-value">{{ in_array($propertyType, ['lettings', 'both']) ? $petsAllowed : 'N/A' }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Collecting Rent:</span>
                            <span class="feature-value">{{ $collectingRent }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Compliance & Media Quick View --}}
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Compliance</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_compliance" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-label">EPC Required:</span>
                            <span class="feature-value">{{ $epcRequired }}</span>
                        </div>
                        @if($epcRequired === 'Yes')
                        <div class="feature-item">
                            <span class="feature-label">EPC Rating:</span>
                            <span class="feature-value">{{ $epcRating ?: 'N/A' }}</span>
                        </div>
                        @endif
                        <div class="feature-item">
                            <span class="feature-label">Gas:</span>
                            <span class="feature-value">{{ $isGas }}</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label">Market On:</span>
                            <span class="feature-value">{{ $marketOn ? implode(', ', $marketOn) : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Accessibility & Media --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            {{-- Accessibility Card --}}
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Accessibility & Location</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_accessibility" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Access Arrangement:</strong>
                        <p class="text-muted mb-0">{{ $accessArrangement ?: 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>Key Highlights:</strong>
                        <p class="text-muted mb-0">{{ $keyHighlights ?: 'N/A' }}</p>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <strong>Nearest Station:</strong>
                            <p class="text-muted mb-0">
                                @if($stations->isNotEmpty())
                                    {{ implode(', ', $stations->toArray()) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong>Nearest School:</strong>
                            <p class="text-muted mb-0">
                                @if($schools->isNotEmpty())
                                    {{ implode(', ', $schools->toArray()) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                    @php
                        $places = $property->nearest_places;
                        if (is_string($places)) { $places = json_decode($places, true) ?: []; }
                        elseif (is_object($places)) { $places = (array) $places; }
                        $places = $places ?? [];
                    @endphp
                    @if(!empty($places))
                    <div class="mt-3">
                        <strong>Nearest Places:</strong>
                        <div class="row g-2 mt-1">
                            @foreach($places as $name => $distance)
                                <div class="col-sm-6">
                                    <span class="text-muted">{{ ucfirst($name) }}:</span> {{ $distance }} km
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($usefulInformation)
                    <div class="mt-3">
                        <strong>Useful Information:</strong>
                        <p class="text-muted mb-0">{{ $usefulInformation }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            {{-- Media Card --}}
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="card-title mb-0">Media</span>
                    @can('edit properties')
                    <button class="btn btn-sm btn-outline-primary editForm" data-form="property_media" data-id="{{ $property->id }}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if($photos)
                        <div class="mb-3">
                            <strong>Photos:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach(explode(',', $photos) as $photo_id)
                                    @php $trimmedId = trim($photo_id); @endphp
                                    @if($trimmedId)
                                        <img src="{{ uploaded_asset($trimmedId) }}" alt="Property Photo" class="img-thumbnail property-media-thumb" style="width: 80px; height: 60px; object-fit: cover; cursor: pointer;" onclick="openImageModal('{{ uploaded_asset($trimmedId) }}')">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($floorPlan)
                        <div class="mb-3">
                            <strong>Floor Plan:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach(explode(',', $floorPlan) as $fp_id)
                                    @php $trimmedId = trim($fp_id); @endphp
                                    @if($trimmedId)
                                        <img src="{{ uploaded_asset($trimmedId) }}" alt="Floor Plan" class="img-thumbnail property-media-thumb" style="width: 80px; height: 60px; object-fit: cover; cursor: pointer;" onclick="openImageModal('{{ uploaded_asset($trimmedId) }}')">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($view360)
                        <div class="mb-3">
                            <strong>360° View:</strong>
                            <a href="{{ $view360 }}" target="_blank">View 360°</a>
                        </div>
                    @endif
                    @if($youtubeUrl || $instagramUrl)
                        <div class="mb-3">
                            @if($youtubeUrl)
                                <div class="mb-1">
                                    <strong>YouTube:</strong>
                                    <a href="{{ $youtubeUrl }}" target="_blank">Watch</a>
                                </div>
                            @endif
                            @if($instagramUrl)
                                <div>
                                    <strong>Instagram:</strong>
                                    <a href="{{ $instagramUrl }}" target="_blank">View</a>
                                </div>
                            @endif
                        </div>
                    @endif
                    @if(!$photos && !$floorPlan && !$view360 && !$youtubeUrl && !$instagramUrl)
                        <p class="text-muted mb-0">No media uploaded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
