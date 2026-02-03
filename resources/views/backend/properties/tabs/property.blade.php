<div class="flex flex_row gap_16">
    <div class="pv_image">
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
            if (isset($property) && isset($property->state)) {
                $addressParts[] = $property->state;
            }
            if (isset($property) && isset($property->postcode)) {
                $addressParts[] = $property->postcode;
            }

            // Join all parts with commas and spaces
            $address = implode(', ', $addressParts);

            $propertyType = $property->property_type ?? '';
            $transactionType = $property->transaction_type ?? '';
            $specificPropertyType = $property->specific_property_type ?? '';
            $bedroom = $property->bedroom ?? '';
            $bathroom = $property->bathroom ?? '';
            $reception = $property->reception ?? '';
            $parking = booleanToYesNo($property->parking) ?? '';
            $balcony = booleanToYesNo($property->balcony) ?? '';
            $garden = booleanToYesNo($property->garden) ?? '';
            $service = $property->service ?? '';
            $collectingRent = booleanToYesNo($property->collecting_rent) ?? '';
            $floor = $property->floor ?? '';
            $squareFeet = $property->square_feet ?? '';
            $squareMeter = $property->square_meter ?? '';
            $aspects = $property->aspects ?? '';
            $currentStatus = $property->current_status ?? '';
            $statusDescription = $property->status_description ?? '';
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
        <img src="{{ asset('/asset/images/temp-property.webp') }}" alt="property">
    </div>

    <div class="pv_content">

        <div class="pvc_ref_id">Ref: 1234SSSD</div>
        <div class="pvc_poperty_name">{{ $address }}</div>
        <div class="rs_property_icons">
            <div class="bed_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/bed.svg') }} " alt="bedroom"> {{$bedroom}}
            </div>
            <div class="bath_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/bath.svg') }} " alt="bathroom"> {{$bathroom}}
            </div>
            <div class="floors_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/floor.svg') }} " alt="Floors"> {{$floor}}
            </div>
            <div class="living_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/sofa.svg') }} " alt="sofa"> 2
            </div>
        </div>
        <div class="pvc_price">
            Price: <span>{{getPoundSymbol()}}{{$price}}</span>
        </div>
        <div class="row">
            <!-- Property Type -->
            <div class="col-md-4 mb-3">
                <strong>Property Type:</strong>
                <p>{{ $property->property_type ?? 'Not specified' }}</p>
            </div>

            <!-- Transaction Type -->
            <div class="col-md-4 mb-3">
                <strong>Transaction Type:</strong>
                <p>{{ $property->transaction_type ?? 'Not specified' }}</p>
            </div>

            <!-- Specific Property Type -->
            <div class="col-md-4 mb-3">
                <strong>Specific Property Type:</strong>
                <p>{{ $property->specific_property_type ?? 'Not specified' }}</p>
            </div>
        </div>

        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_type">Type: <strong> Apparment</strong></div>
            </div>
            <div class="rs_col">
                <div class="pv_availability">Availability: <strong>{{ $availableFrom }}</strong></div>
            </div>
        </div>
        {{-- rs_row end  --}}
        <div class="rs_row">
            <div class="rs_col">
                <div class="pv_status">Status: <strong> For Sale</strong></div>
            </div>
            <div class="rs_col">
                <div class="pv_service">Service: <strong>{{$service}}</strong></div>
            </div>
        </div>
        {{-- rs_row end  --}}

    </div>
    {{-- pv_content end  --}}
</div>
<div class="pvd_content_wrapper">
    {{-- mobile view only start  --}}
    <div class="pv_content mobile_only">
        <div class="rs_property_icons">
            <div class="bed_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/bed.svg') }} " alt="bedroom"> 2
            </div>
            <div class="bath_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/bath.svg') }} " alt="bathroom"> 1
            </div>
            <div class="floors_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/floor.svg') }} " alt="Floors">2
            </div>
            <div class="living_icon rs_tooltip">
                <img src=" {{ asset('asset/images/svg/icons/sofa.svg') }} " alt="sofa"> 2
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
    <div class="pvd_other_content border_bottom">
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
    </div>
    {{-- pvd_other_content end --}}
    <div class="pvd_features">
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
    </div>
    {{-- pvd_features end --}}
</div>
{{-- pvd_content_wrapper end --}}
