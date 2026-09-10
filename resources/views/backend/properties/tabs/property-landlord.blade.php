@php
    $unset = 'Not set';
    $homeType = $property->specific_property_type ?: '';
    $bedroom = $property->bedroom ?? '';
    $bathroom = $property->bathroom ?? '';
    $reception = $property->reception ?? '';
    $floor = $property->floor ?? '';
    $squareMeter = $property->square_meter ?? '';
    $squareFeet = $property->square_feet ?? '';
    $parking = booleanToYesNo($property->parking);
    $garden = booleanToYesNo($property->garden);
    $balcony = booleanToYesNo($property->balcony);
    $petsAllowed = booleanToYesNo($property->pets_allow);
    $tenure = $property->tenure ?? '';
    $epcRating = $property->epc_rating ?? '';
    $lettingStatus = $property->letting_current_status ?? '';
    $lettingPrice = $property->letting_price ?? '';
    $councilTaxBand = $property->council_tax_band ?? '';
    $description = $property->letting_status_description ?? '';
    $photos = $property->photos ?? '';
    $usefulInformation = trim((string) ($property->useful_information ?? ''));
    $showSources = $usefulInformation !== '' && ! str_starts_with(strtolower($usefulInformation), 'sources:');
    $localAuthority = $property->localAuthority->display_name ?? '';
    $activeTenancy = \App\Models\Tenancy::query()
        ->where('property_id', $property->id)
        ->where('status', 'Active')
        ->orderByDesc('move_in')
        ->first();
    $rent = $lettingPrice ?: ($activeTenancy->rent ?? null);
    $features = array_filter([
        $floor ? ['Floor', $floor] : null,
        $parking === 'Yes' ? ['Parking', $property->parking_location ?: 'Yes'] : null,
        $garden === 'Yes' ? ['Garden', 'Yes'] : null,
        $balcony === 'Yes' ? ['Balcony', 'Yes'] : null,
        $petsAllowed !== '' ? ['Pets', $petsAllowed] : null,
        $squareMeter ? ['Area', number_format((float) $squareMeter, 0).' sqm'] : null,
        $squareFeet && ! $squareMeter ? ['Area', number_format((float) $squareFeet, 0).' sqft'] : null,
        $tenure ? ['Tenure', $tenure] : null,
        $councilTaxBand ? ['Council tax', $councilTaxBand] : null,
        $localAuthority ? ['Local authority', $localAuthority] : null,
    ]);
@endphp

<div class="property-overview-dashboard pcc-overview-landlord">
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-building"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Type</div>
                    <div class="fact-value text-capitalize">{{ $homeType ?: $unset }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
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
                <div class="fact-icon"><i class="bi bi-currency-pound"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Rent</div>
                    <div class="fact-value">{{ $rent ? '£'.number_format((float) $rent, 0).'/mo' : $unset }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="fact-card">
                <div class="fact-icon"><i class="bi bi-house-door"></i></div>
                <div class="fact-content">
                    <div class="fact-label">Let status</div>
                    <div class="fact-value text-capitalize">{{ $lettingStatus ?: $unset }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            @if($description)
                <div class="card mb-3">
                    <div class="card-header"><span class="card-title mb-0">Description</span></div>
                    <div class="card-body">
                        <x-toggle-description :text="$description" :limit="220" />
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-header"><span class="card-title mb-0">Details</span></div>
                <div class="card-body">
                    @if($features)
                        <div class="features-grid">
                            @foreach($features as $feature)
                                <div class="feature-item">
                                    <span class="feature-label">{{ $feature[0] }}</span>
                                    <span class="feature-value text-capitalize">{{ $feature[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No extra details on file yet. Use Edit in the header to add them.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><span class="card-title mb-0">Current let</span></div>
                <div class="card-body">
                    @if($activeTenancy)
                        <div class="feature-item mb-2">
                            <span class="feature-label">Tenancy</span>
                            <span class="feature-value">Active</span>
                        </div>
                        @if($activeTenancy->move_in)
                            <div class="feature-item mb-2">
                                <span class="feature-label">Move in</span>
                                <span class="feature-value">{{ $activeTenancy->move_in->format('d M Y') }}</span>
                            </div>
                        @endif
                        @if($activeTenancy->move_out)
                            <div class="feature-item mb-2">
                                <span class="feature-label">Move out</span>
                                <span class="feature-value">{{ $activeTenancy->move_out->format('d M Y') }}</span>
                            </div>
                        @endif
                        <a href="{{ route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'tenancy']) }}" class="btn btn-sm btn-outline-primary mt-2">Open tenancy</a>
                    @else
                        <p class="text-muted mb-2">No active tenancy on this property.</p>
                        @can('create tenancies')
                            <a href="{{ route('admin.tenancies.create', ['property_id' => $property->id]) }}" class="btn btn-sm pcc-btn-ink">Add tenancy</a>
                        @endcan
                    @endif
                </div>
            </div>

            @if($epcRating || $stations->isNotEmpty() || $schools->isNotEmpty() || $showSources)
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="card-title mb-0">Location &amp; EPC</span>
                        @if($epcRating)
                            <a href="{{ route('admin.properties.index', ['property_id' => $property->id, 'tabname' => 'compliance']) }}" class="btn btn-sm btn-outline-primary">Certificates</a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($epcRating)
                            <div class="feature-item mb-2">
                                <span class="feature-label">EPC</span>
                                <span class="feature-value">{{ strtoupper($epcRating) }}</span>
                            </div>
                        @endif
                        @if($stations->isNotEmpty())
                            <div class="feature-item mb-2">
                                <span class="feature-label">Station</span>
                                <span class="feature-value">{{ implode(', ', $stations->toArray()) }}</span>
                            </div>
                        @endif
                        @if($schools->isNotEmpty())
                            <div class="feature-item mb-2">
                                <span class="feature-label">School</span>
                                <span class="feature-value">{{ implode(', ', $schools->toArray()) }}</span>
                            </div>
                        @endif
                        @if($showSources)
                            <p class="text-muted mb-0 small">{{ $usefulInformation }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if($photos)
                <div class="card mb-3">
                    <div class="card-header"><span class="card-title mb-0">Photos</span></div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(explode(',', $photos) as $photo_id)
                                @php $trimmedId = trim($photo_id); @endphp
                                @if($trimmedId)
                                    <img src="{{ uploaded_asset($trimmedId) }}" alt="Property photo" class="img-thumbnail property-media-thumb" style="width: 80px; height: 60px; object-fit: cover; cursor: pointer;" onclick="openImageModal('{{ uploaded_asset($trimmedId) }}')">
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
