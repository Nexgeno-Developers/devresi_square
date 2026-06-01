@php
// echo '<pre>';
// var_dump($propertyId);
// echo '</pre>';
// echo '<pre>';
// var_dump($property);
// echo '</pre>';

// Build the address string while checking for null values
$addressParts = [];

if (isset($property) && isset($property->line_1)) {
    $addressParts[] = $property->line_1;
}
if (isset($property) && isset($property->line_2)) {
    $addressParts[] = $property->line_2;
}
if (isset($property) && isset($property->city)) {
    $addressParts[] = $property->city;
}
if (isset($property) && isset($property->country)) {
    $addressParts[] = $property->country;
}
if (isset($property) && isset($property->postcode)) {
    $addressParts[] = $property->postcode;
}

// Join all parts with commas and spaces
$address = implode(', ', $addressParts);

$propRefNo = $property->prop_ref_no ?? '';
$propertyType = $property->property_type ?? '';
$transactionType = $property->transaction_type ?? '';
$specificPropertyType = $property->specific_property_type ?? '';
$bedroom = $property->bedroom ?? '';
$bathroom = $property->bathroom ?? '';
$reception = $property->reception ?? '';
$parkingLocation = $property->parking_location ?? 'N/A';
$parking = booleanToYesNo($property->parking) ?? '';
$balcony = booleanToYesNo($property->balcony) ?? '';
$garden = booleanToYesNo($property->garden) ?? '';
$petsAllowed = booleanToYesNo($property->pets_allow) ?? '';
$service = $property->service ?? '';
$collectingRent = booleanToYesNo($property->collecting_rent) ?? '';
$floor = $property->floor ?? '';
$squareFeet = $property->square_feet ?? '';
$squareMeter = $property->square_meter ?? '';
$aspects = $property->aspects ?? '';
$currentStatus = $property->current_status ?? '';
$lettingCurrentStatus = $property->letting_current_status ?? '';
$salesCurrentStatus = $property->sales_current_status ?? '';
$salesStatusDescription = $property->sales_status_description ?? '';
$lettingStatusDescription = $property->letting_status_description ?? '';
$availableFrom = formatDate($property->available_from) ?? '';
$marketOn = $property->market_on ?? '';
$features = $property->features ?? '';
$furniture = jsonDecodeAndPrint($property->furniture) ?? '';
$kitchen = jsonDecodeAndPrint($property->kitchen) ?? '';
$heatingCooling = jsonDecodeAndPrint($property->heating_cooling) ?? '';
$safety = jsonDecodeAndPrint($property->safety) ?? '';
$other = jsonDecodeAndPrint($property->other) ?? '';
$price = $property->price ?? '';
$groundRent = $property->ground_rent ?? '';
$serviceCharge = $property->service_charge ?? '';
$annualCouncilTax = $property->annual_council_tax ?? '';
$councilTaxBand = $property->council_tax_band ?? '';
$lettingPrice = $property->letting_price ?? '';
$tenure = $property->tenure ?? '';
$lengthOfLease = $property->length_of_lease ?? '';
$epcRequired = booleanToYesNo($property->epc_required) ?? '';
$epcRating = $property->epc_rating ?? '';
$isGas = $property->is_gas ?? '';
$photos = $property->photos ?? '';
$floorPlan = $property->floor_plan ?? '';
$view360 = $property->view_360 ?? '';
$videoUrl = $property->video_url ?? '';
$designation = $property->designation ?? '';
$branch = $property->branch ?? '';
$commissionPercentage = $property->commission_percentage ?? '';
$commissionAmount = $property->commission_amount ?? '';

// Merge all features into one array
$allFeatures = array_merge(
    explode(', ', $furniture),
    explode(', ', $kitchen),
    explode(', ', $heatingCooling),
    explode(', ', $safety),
    explode(', ', $other)
);

// Split the array into two halves
$halfCount = ceil(count($allFeatures) / 2); // to handle odd numbers
$firstHalf = array_slice($allFeatures, 0, $halfCount);
$secondHalf = array_slice($allFeatures, $halfCount);

@endphp
<div class="flex flex_row gap_16">
    {{-- <div class="pv_image">
        <img src="{{ asset('/asset/images/temp-property.webp') }}" alt="property">
    </div> --}}
    
    <div class="pv_content w-100">

       <div class="pvc_property_name_wrapper">
            <div>
                <div class="pvc_ref_id"> <strong> Property Ref: {{$propRefNo}} </strong></div>
                <div class="pvc_poperty_name">{{ $address }}</div>
            </div>
            @can('delete properties')
            @if (isset($property) && isset($property->id))
                <!-- Delete Button -->
                <button type="button" class="float-end btn btn-sm btn-outline-danger"
                onclick="confirmModal('{{ url(route('admin.properties.delete', $property->id)) }}', responseHandler)">
                <i class="mdi mdi-delete" title="Delete"></i>
                Delete
                </button>
            @endif
            @endcan
        </div>
        {{-- <div class="rs_property_icons">
            <div class="bed_icon rs_tooltip" data-label="Bedroom">
                <img src=" {{ asset('asset/images/svg/icons/bed.svg') }} " alt="bedroom"> {{$bedroom}}
            </div>
            <div class="bath_icon rs_tooltip" data-label="Bathroom">
                <img src=" {{ asset('asset/images/svg/icons/bath.svg') }} " alt="bathroom"> {{$bathroom}}
            </div>
            <div class="floors_icon rs_tooltip" data-label="Floors">
                <img src=" {{ asset('asset/images/svg/icons/floor.svg') }} " alt="Floors"> {{$floor}}
            </div>
            <div class="living_icon rs_tooltip" data-label="Sofa">
                <img src=" {{ asset('asset/images/svg/icons/sofa.svg') }} " alt="sofa"> {{ $reception }}
            </div>
        </div> --}}
        
        {{-- <div class="d-flex justify-content-between align-items-center border rounded-4 p-3 mt-3">
            <span class="fw-semibold">Important Note
            <div class="notes-update-ajax" id="section-notes-{{ $property->id }}">
                @include("backend.properties.popup_forms.notes", ['property' => $property])
            </div>
            </span>
            <button class="btn btn-outline-danger btn-sm editForm" data-form="{{ 'notes' }}" data-id="{{ $property->id }}">
                Edit
            </button>
        </div> --}}
        
    </div>
    {{-- pv_content end  --}}
</div>

@canany(['edit important note', 'view important note'])
<div class="property_note">
    <span class="fw-semibold">Important Note
    <div class="notes-update-ajax" id="section-notes-{{ $property->id }}">
        @include("backend.properties.popup_forms.notes", ['property' => $property])
    </div>
    </span>
    @can('edit important note')
    <button class="btn btn-outline-danger btn-sm editForm" data-form="{{ 'notes' }}" data-id="{{ $property->id }}">
        Edit
    </button>
    @endcan
</div>
@endcanany

<div class="property_note">
    <span class="fw-semibold">
    <div class="property_status-update-ajax" id="section-property_status-{{ $property->id }}">
        @include("backend.properties.popup_forms.property_status", ['property' => $property])
    </div>
    </span>
    @can('edit properties')
    <button class="btn btn-outline-danger btn-sm editForm" data-form="{{ 'property_status' }}" data-id="{{ $property->id }}">
        Edit
    </button>
    @endcan
</div>

<div class="pvd_content_wrapper">
<!-- Button to Collapse/Expand All -->
<div class="d-flex justify-content-end mb-3">
    <a id="toggleAll" class="pointer underline">Collapse All</a>
</div>
    
    <div class="accordion" id="propertyAccordion">

        @php
        $formSections = [
            ['key' => 'availability_pricing', 'title' => 'Availability & Pricing', 'order' => 1],
            ['key' => 'property_features', 'title' => 'Property Features', 'order' => 3],
            ['key' => 'property_info', 'title' => 'Property Information', 'order' => 2],
            ['key' => 'property_services', 'title' => 'Service', 'order' => 4],
            // ['key' => 'property_accessibility', 'title' => 'Accessibility', 'order' => 6],
            // ['key' => 'property_compliance', 'title' => 'Compliance', 'order' => 5],
            // ['key' => 'property_media', 'title' => 'Media', 'order' => 7],
            // ['key' => 'property_status', 'title' => 'Status', 'order' => 7],
            // Add more sections with order values as needed
        ];
    
        // Sort by 'order' key
        usort($formSections, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
    @endphp
    @foreach($formSections as $index => $section)
        @php
            $formType = $section['key'];
            $title = $section['title'];
            $isFirst = $index === 0;
        @endphp

        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $formType }}">
                <button 
                    class="accordion-button {{ $isFirst ? '' : 'collapsed' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#collapse-{{ $formType }}" 
                    aria-expanded="{{ $isFirst ? 'true' : 'false' }}" 
                    aria-controls="collapse-{{ $formType }}">
                    {{ $title }}
                </button>
            </h2>
            <div 
                id="collapse-{{ $formType }}" 
                class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" 
                aria-labelledby="heading-{{ $formType }}">
                @can('edit properties')
                <button class="btn btn_outline_secondary mt-2 float-end editForm" data-form="{{ $formType }}" data-id="{{ $property->id }}">
                    Edit
                </button>
                @endcan
                <div class="accordion-body" id="section-{{ $formType }}-{{ $property->id }}">
                    @include("backend.properties.popup_forms.$formType", ['property' => $property])
                </div>
            </div>
        </div>
    @endforeach

    {{-- @foreach($formSections as $section)
        @php
            $formType = $section['key'];
            $title = $section['title'];
        @endphp
    
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $formType }}">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $formType }}" aria-expanded="true" aria-controls="collapse-{{ $formType }}">
                    {{ $title }}
                </button>
            </h2>
            <div id="collapse-{{ $formType }}" class="accordion-collapse collapse show" aria-labelledby="heading-{{ $formType }}">
                <button class="btn btn_outline_secondary mt-2 float-end editForm" data-form="{{ $formType }}" data-id="{{ $property->id }}">
                    Edit
                </button>
                <div class="accordion-body" id="section-{{ $formType }}-{{ $property->id }}">
                    @include("backend.properties.popup_forms.$formType", ['property' => $property])
                </div>
            </div>
        </div>
    @endforeach         --}}
    </div>

    {{-- mobile view only start  --}}
    <div class="pv_content mobile_only">
        <div class="rs_property_icons">
            <div class="bed_icon rs_tooltip"  data-label="Bedroom">
                <img src=" {{ asset('asset/images/svg/icons/bed.svg') }} " alt="bedroom"> {{$bedroom}}
            </div>
            <div class="bath_icon rs_tooltip"  data-label="Bathroom">
                <img src=" {{ asset('asset/images/svg/icons/bath.svg') }} " alt="bathroom"> {{$bathroom}}
            </div>
            <div class="floors_icon rs_tooltip"  data-label="Floors">
                <img src=" {{ asset('asset/images/svg/icons/floor.svg') }} " alt="Floors">{{$floor}}
            </div>
            <div class="living_icon rs_tooltip"  data-label="Sofa">
                <img src=" {{ asset('asset/images/svg/icons/sofa.svg') }} " alt="sofa"> {{ $reception }}
            </div>
        </div>
        <div class="pvc_ref_id">Ref: 1234SSSD</div>
        <div class="pvc_poperty_name">{{ $address }}</div>
        <div class="pvc_price">
            Price: <span>£3000</span>
        </div>
        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_type">Type: <strong> Apparment</strong></div>
            </div>
            <div class="rs_col">
                <div class="pv_availability">Availability: <strong>11/02/25</strong></div>
            </div>
        </div>
        {{-- rs_row end  --}}
        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_status">Status: <strong> For Sale</strong></div>
            </div>
            <div class="rs_col">
                <div class="pv_service">Service: <strong>Let Only</strong></div>
            </div>
        </div>
        {{-- rs_row end  --}}

    </div>
    {{-- pv_content end  --}}
    {{-- mobile view only end  --}}

    {{-- <div class="pvd_other_content border_bottom">
        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="row">
                    <div class="col-lg-5 col-6 mb-2 ">Furniture</div>
                    <div class="col-lg-7 col-6 mb-2 text-lg-start text-end">{{$furniture}}</div>
                    <div class="col-lg-5 col-6 mb-2 ">Parking</div>
                    <div class="col-lg-7 col-6 mb-2 text-lg-start text-end">{{$parking}}</div>
                    <div class="col-lg-5 col-6 mb-2 ">Balcony</div>
                    <div class="col-lg-7 col-6 mb-2 text-lg-start text-end">{{$balcony}}</div>
                    <div class="col-lg-5 col-6 mb-2 ">Garden</div>
                    <div class="col-lg-7 col-6 mb-2 text-lg-start text-end">{{$garden}}</div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="row">
                    <div class="col-lg-6 col-6 mb-2 ">Collecting Rent</div>
                    <div class="col-lg-6 col-6 mb-2 text-lg-start text-end">{{$collectingRent}}</div>
                    <div class="col-lg-6 col-6 mb-2 ">Area Sqr. Feet</div>
                    <div class="col-lg-6 col-6 mb-2 text-lg-start text-end">{{$squareFeet}}</div>
                    <div class="col-lg-6 col-6 mb-2 ">Area Sqr. Meter</div>
                    <div class="col-lg-6 col-6 mb-2 text-lg-start text-end">{{$squareMeter}}</div>
                    <div class="col-lg-6 col-6 mb-2 ">Aspects</div>
                    <div class="col-lg-6 col-6 mb-2 text-lg-start text-end">{{$aspects}}</div>
                </div>

            </div>
        </div>
    </div> --}}
    {{-- pvd_other_content end --}}
    {{-- <div class="pvd_features">
        <div class="pv_sub_title mb-4">
            Features
        </div>
        <div class="row features_list_warpper">
            <div class="col-lg-6">
                <ul class="features_list">
                    @foreach($firstHalf as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="col-lg-6">
                <ul class="features_list">
                    @foreach($secondHalf as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div> --}}
    {{-- pvd_features end --}}
</div>
{{-- pvd_content_wrapper end --}}
