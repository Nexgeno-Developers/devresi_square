@php
$furniture = jsonDecodeAndPrint($property->furniture) ?? '';
$kitchen = jsonDecodeAndPrint($property->kitchen) ?? '';
$heatingCooling = jsonDecodeAndPrint($property->heating_cooling) ?? '';
$safety = jsonDecodeAndPrint($property->safety) ?? '';
$other = jsonDecodeAndPrint($property->other) ?? '';
@endphp
@if (!isset($editMode) || !$editMode)
    <!-- Display View Mode -->

    <div class="accordion_inner">
        <div class="accordion_features_wrapper">
            <div class="accordion_features_item">
                <div  class="accordion_features_label">
                    <div class="accordion_inner_heading">Furniture</div> 
                </div>
                <div  class="accordion_features_content">
                    {{ $furniture ?: 'N/A' }}
                </div>
            </div>
            <div class="accordion_features_item">
                <div  class="accordion_features_label">
                    <div class="accordion_inner_heading">Kitchen</div> 
                </div>
                <div  class="accordion_features_content">
                    {{ $kitchen ?: 'N/A' }}
                </div>
            </div>
            {{-- <div class="accordion_features_item">
                <div  class="accordion_features_label">
                    <div class="accordion_inner_heading">Heating and Cooling</div> 
                </div>
                <div  class="accordion_features_content">
                    {{ $heatingCooling ?: 'N/A' }}
                </div>
            </div> --}}
            <div class="accordion_features_item">
                <div  class="accordion_features_label">
                    <div class="accordion_inner_heading">Safety</div> 
                </div>
                {{ $safety ?: 'N/A' }}
            </div>
            <div class="accordion_features_item">
                <div  class="accordion_features_label">
                    <div class="accordion_inner_heading">Other</div> 
                <div  class="accordion_features_content">
                    {{ $other ?: 'N/A' }}
                </div>
            </div>
        </div>
        
        <!-- Display View Mode -->
        <div class=" ">
            <div class="accordion_inner_heading mb-2">Rooms </div>
            <div class="accordion_features_rooms">
                <div class="accordion_features_item rooms">
                    <span class="gray-950 fw-400"> {{ $property->bedroom }} </span> <sapn class="gray-500"> Bedrooms </sapn>
                </div>
                <div class="accordion_features_item rooms">
                    <span class="gray-950 fw-400">{{ $property->bathroom }}  </span> <sapn class="gray-500"> Bathrooms </sapn>
                </div>
                <div class="accordion_features_item rooms">
                    <span class="gray-950 fw-400"> {{ $property->reception }}</span> <sapn class="gray-500"> Reception Rooms </sapn>
                </div>
                <div class="accordion_features_item rooms">
                    <span class="gray-950 fw-400 capitalize">{{ $property->floor }}</span> <sapn class="gray-500"> Floor </sapn>
                </div>
            </div>
        </div>
        
        
        <div class=" ">
            <div class="accordion_inner_heading mb-2">Balcony</div>
            <div class="accordion_features_item balcony">
                <div><span class="gray-500">Balcony:</span> <span class="gray-950 fw-400">{{ $property->balcony == '1' ? 'Yes' : 'No' }}</span></div>
                <div><span class="gray-500">Garden: </span><span class="gray-950 fw-400"> {{ $property->garden == '1' ? 'Yes' : 'No' }}</span></div>
                <div><span class="gray-500">Aspects:</span><span class="gray-950 fw-400"> {{ $property->aspects ?? 'N/A' }}</span></div>
            </div>
        </div>
        {{-- <div class=" ">
            <div class="accordion_inner_heading mb-2">Rent</div>
            <div class="accordion_features_item">
                <div><span class="gray-500">Collecting Rent: </span> <span class="gray-950 fw-400">{{ $property->collecting_rent == '1' ? 'Yes' : 'No' }}</span></div>
            </div>
        </div> --}}
        <div class=" ">
            <div class="accordion_inner_heading mb-2">Area</div>
            <div class="accordion_features_item area">
                <div>
                    <span class="gray-500">Square Feet: </span>
                    <span class="gray-950 fw-400">
                        {{ isset($property->square_feet) ? number_format($property->square_feet, 2) . ' sqft' : 'N/A' }}
                    </span>
                </div>
                
                <div>
                    <span class="gray-500">Square Meter:</span>
                    <span class="gray-950 fw-400">
                        {{ isset($property->square_meter) ? number_format($property->square_meter, 2) . ' sqm' : 'N/A' }}
                    </span>
                </div>                
            </div>
        </div>
        </div>
    </div>

@else
    <!-- Form Input Mode -->
    <form id="propertyFeaturesForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}" />
        <input type="hidden" name="form_type" value="property_features" />

        <div class="feature_edit_form">
            <div class="rounded_check_btn">
                    <div class="form-group ">
                        <div class="accordion_inner_heading mb-2">Furniture</div>
                            @foreach(['Furnished' => 'Furnished', 'Unfurnished' => 'Unfurnished', 'Flexible' => 'Flexible'] as $key => $value)
                                @php
                                    // Decode the furniture field if it's a JSON string.
                                    $furniture = isset($property) && is_string($property->furniture) ? json_decode($property->furniture, true) : [];
                                @endphp  
                            @endforeach  
                            <div class="form-check">
                                <input class="form-check-input hidden" type="checkbox" name="furniture[]" value="{{ $key }}" id="furniture_{{ $key }}" 
                                    {{ in_array($key, $furniture) ? 'checked' : '' }}>
                                <label  class="checkbox_btn" for="furniture_{{ $key }}">{{ $value }}</label>
                            </div>
                    </div> {{-- form-group end --}}
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Kitchen</div>
                        <div class="check_btn_wrapper">
                            @foreach ([
                                'Undercounter refrigerator without freezer' => 'Undercounter refrigerator without freezer',
                                'Dishwasher' => 'Dishwasher',
                                'Gas oven' => 'Gas oven',
                                'Gas hob' => 'Gas hob',
                                'Washing machine' => 'Washing machine',
                                'Dryer' => 'Dryer',
                                'Electric hob' => 'Electric hob',
                                'Electric oven' => 'Electric oven',
                                'Washer' => 'Washer',
                                'Washer Dryer' => 'Washer Dryer',
                                'Undercounter refrigerator with freezer' => 'Undercounter refrigerator with freezer',
                                'Tall refrigerator with freezer' => 'Tall refrigerator with freezer',
                            ] as $key => $value)
                                @php
                                    // Decode the kitchen field if it's a JSON string.
                                    $kitchen =
                                        isset($property) && is_string($property->kitchen) ? json_decode($property->kitchen, true) : [];
                                @endphp
                                <div class="form-check">
                                    <input class="hidden" type="checkbox" name="kitchen[]" value="{{ $key }}"
                                        id="kitchen_{{ $key }}" {{ in_array($key, $kitchen) ? 'checked' : '' }}>
                                    <label class="checkbox_btn" for="kitchen_{{ $key }}">{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>{{-- check_btn_wrapper end --}}
                    </div>{{-- form-group end --}}

                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Heating and Cooling</div>
                        <div class="check_btn_wrapper">
                            @foreach ([
                                'Air conditioning' => 'Air conditioning',
                                'Underfloor heating' => 'Underfloor heating',
                                'Electric' => 'Electric',
                                'Gas' => 'Gas',
                                'Central heating' => 'Central heating',
                                'Comfort cooling' => 'Comfort cooling',
                                'Portable heater' => 'Portable heater',
                            ] as $key => $value)
                            @php
                                // Decode the heating_cooling field if it's a JSON string.
                                $heatingCooling =
                                    isset($property) && is_string($property->heating_cooling)
                                        ? json_decode($property->heating_cooling, true)
                                        : [];
                                @endphp
                                <div class="form-check">
                                    <input class="hidden" type="checkbox" name="heating_cooling[]"
                                        value="{{ $key }}" id="heating_cooling_{{ $key }}"
                                        {{ in_array($key, $heatingCooling) ? 'checked' : '' }}>
                                    <label class="checkbox_btn" for="heating_cooling_{{ $key }}">{{ $value }}</label>
                                </div>
                            @endforeach
                        </div>{{-- check_btn_wrapper end --}}
                    </div>{{-- form-group end --}}

                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Safety</div>
                        <div class="check_btn_wrapper">
                            @foreach ([
                                'External CCTV Intruder alarm system' => 'External CCTV Intruder alarm system',
                                'Smoke alarm' => 'Smoke alarm (Legal requirement)',
                                'Carbon monoxide detector' => 'Carbon monoxide detector',
                                'Window locks' => 'Window locks',
                                'Security key lock' => 'Security key lock',
                            ] as $key => $value)
                            @php
                                // Decode the safety field if it's a JSON string.
                                $safety =
                                    isset($property) && is_string($property->safety) ? json_decode($property->safety, true) : [];
                            @endphp
                            <div class="form-check">
                                <input class="hidden" type="checkbox" name="safety[]" value="{{ $key }}"
                                    id="safety_{{ $key }}" {{ in_array($key, $safety) ? 'checked' : '' }}>
                                <label class="checkbox_btn" for="safety_{{ $key }}">{{ $value }}</label>
                            </div>
                            @endforeach
                        </div>{{-- check_btn_wrapper end --}}
                    </div>{{-- form-group end --}}

                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Other</div>
                        <div class="check_btn_wrapper">
                            @foreach ([
                                'Roof Garden' => 'Roof Garden',
                                'Business Centre' => 'Business Centre',
                                'Concierge' => 'Concierge',
                                'Lift' => 'Lift',
                                'Pets Allowed' => 'Pets Allowed',
                                'Pets Allowed With Licence' => 'Pets Allowed With Licence',
                                'TV' => 'TV',
                                'Fireplace' => 'Fireplace',
                                'Wood flooring' => 'Wood flooring',
                                'Double glazing' => 'Double glazing',
                                'Not suitable for wheelchair users' => 'Not suitable for wheelchair users',
                                'Gym' => 'Gym',
                                'None' => 'None',
                            ] as $key => $value)
                            @php
                                // Decode the other field if it's a JSON string.
                                $other = isset($property) && is_string($property->other) ? json_decode($property->other, true) : [];
                            @endphp
                            <div class="form-check">
                                <input class="hidden" type="checkbox" name="other[]" value="{{ $key }}"
                                    id="other_{{ $key }}" {{ in_array($key, $other) ? 'checked' : '' }}>
                                <label class="checkbox_btn" for="other_{{ $key }}">{{ $value }}</label>
                            </div>
                            @endforeach
                        </div>{{-- check_btn_wrapper end --}}
                    </div>{{-- form-group end --}}
                </div>
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Bedrooms</div>
                            <div class="radio_bts_square">
                                <input type="radio" name="bedroom" id="bedroomStudio" value="studio"
                                    {{ isset($property) && $property->bedroom == 'studio' ? 'checked' : '' }} required> <label
                                    for="bedroomStudio">Studio </label>
                                <input type="radio" name="bedroom" id="bedroom1" value="1"
                                    {{ isset($property) && $property->bedroom == '1' ? 'checked' : '' }} required> <label
                                    for="bedroom1">1 </label>
                                <input type="radio" name="bedroom" id="bedroom2" value="2"
                                    {{ isset($property) && $property->bedroom == '2' ? 'checked' : '' }} required> <label
                                    for="bedroom2">2 </label>
                                <input type="radio" name="bedroom" id="bedroom3" value="3"
                                    {{ isset($property) && $property->bedroom == '3' ? 'checked' : '' }} required> <label
                                    for="bedroom3">3 </label>
                                <input type="radio" name="bedroom" id="bedroom4" value="4"
                                    {{ isset($property) && $property->bedroom == '4' ? 'checked' : '' }} required> <label
                                    for="bedroom4">4 </label>
                                <input type="radio" name="bedroom" id="bedroom5" value="5"
                                    {{ isset($property) && $property->bedroom == '5' ? 'checked' : '' }} required> <label
                                    for="bedroom5">5 </label>
                                <input type="radio" name="bedroom" id="bedroom6" value="6+"
                                    {{ isset($property) && $property->bedroom == '6+' ? 'checked' : '' }} required> <label
                                    for="bedroom6">6+ </label>
                            </div>
                            @error('bedroom')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                    </div>{{-- form-group end --}}

                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Bathrooms </div>
                        <div class="radio_bts_square">
                            <input type="radio" name="bathroom" id="bathroom1" value="1"
                                {{ isset($property) && $property->bathroom == '1' ? 'checked' : '' }} required> <label
                                for="bathroom1">1 </label>
                            <input type="radio" name="bathroom" id="bathroom2" value="2"
                                {{ isset($property) && $property->bathroom == '2' ? 'checked' : '' }} required> <label
                                for="bathroom2">2 </label>
                            <input type="radio" name="bathroom" id="bathroom3" value="3"
                                {{ isset($property) && $property->bathroom == '3' ? 'checked' : '' }} required> <label
                                for="bathroom3">3 </label>
                            <input type="radio" name="bathroom" id="bathroom4" value="4"
                                {{ isset($property) && $property->bathroom == '4' ? 'checked' : '' }} required> <label
                                for="bathroom4">4 </label>
                            <input type="radio" name="bathroom" id="bathroom5" value="5"
                                {{ isset($property) && $property->bathroom == '5' ? 'checked' : '' }} required> <label
                                for="bathroom5">5</label>
                            <input type="radio" name="bathroom" id="bathroom6" value="6+"
                                {{ isset($property) && $property->bathroom == '6+' ? 'checked' : '' }} required> <label
                                for="bathroom6">6+ </label>
                        </div>
                        @error('bathroom')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>{{-- form-group end --}}
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Reception Rooms </div>
                        <div class="radio_bts_square">
                            <input type="radio" name="reception" id="reception1" value="1"
                                {{ isset($property) && $property->reception == '1' ? 'checked' : '' }} required />
                            <label for="reception1"> 1 </label>
                            <input type="radio" name="reception" id="reception2" value="2"
                                {{ isset($property) && $property->reception == '2' ? 'checked' : '' }} required />
                            <label for="reception2"> 2 </label>
                            <input type="radio" name="reception" id="reception3" value="3"
                                {{ isset($property) && $property->reception == '3' ? 'checked' : '' }} required />
                            <label for="reception3"> 3 </label>
                            <input type="radio" name="reception" id="reception4" value="4"
                                {{ isset($property) && $property->reception == '4' ? 'checked' : '' }} required />
                            <label for="reception4"> 4 </label>
                            <input type="radio" name="reception" id="reception5" value="5"
                                {{ isset($property) && $property->reception == '5' ? 'checked' : '' }} required />
                            <label for="reception5"> 5 </label>
                            <input type="radio" name="reception" id="reception6" value="6+"
                                {{ isset($property) && $property->reception == '6+' ? 'checked' : '' }} required />
                            <label for="reception6">6+</label>
                        </div>
                        @error('reception')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>{{-- form-group end --}}
            
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Floor </div>
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <select name="floor" class="form-control">
                                    <option value="" disabled
                                        {{ isset($property) && $property->floor == '' ? 'selected' : '' }}>Select a floor
                                    </option>
                                    <option value="basement "
                                        {{ isset($property) && $property->floor == 'basement' ? 'selected' : '' }}>Basement
                                    </option>
                                    <option value="ground floor"
                                        {{ isset($property) && $property->floor == 'ground floor' ? 'selected' : '' }}>Ground
                                        Floor</option>
                                    <option value="1 to 75"
                                        {{ isset($property) && $property->floor == '1 to 75' ? 'selected' : '' }}>1 to 75
                                    </option>
                                </select>
                                @error('floor')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>{{-- form-group end --}}
                    
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Balcony </div>
                        <div class="rs_radio_btns">
                            <div class="flex gap-5">
                                <div>
                                    <input type="radio" name="balcony" id="balcony_no" value="0"
                                        {{ isset($property) && $property->balcony == '0' ? 'checked' : '' }} required />
                                    <label for="balcony_no"> No</label>
                                </div>
                                <div>
                                    <input type="radio" name="balcony" id="balcony_yes" value="1"
                                        {{ isset($property) && $property->balcony == '1' ? 'checked' : '' }} required />
                                    <label for="balcony_yes"> Yes</label>
                                </div>
                                @error('balcony')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
            
                    </div>{{-- form-group end --}}
            
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Garden </div>
                        <div class="rs_radio_btns">
                            <div class="flex gap-5">
                                <div>
                                    <input type="radio" name="garden" id="garden_no" value="0"
                                        {{ isset($property) && $property->garden == '0' ? 'checked' : '' }} required />
                                    <label for="garden_no"> No</label>
                                </div>
                                <div>
                                    <input type="radio" name="garden" id="garden_yes" value="1"
                                        {{ isset($property) && $property->garden == '1' ? 'checked' : '' }} required />
                                    <label for="garden_yes"> Yes</label>
                                </div>
                            </div>
                            @error('garden')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>{{-- form-group end --}}
            
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Aspects </div>
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <select name="aspects" class="form-control" required>
                                    <option value="" disabled
                                        {{ isset($property) && $property->aspects == '' ? 'selected' : '' }}>Select an aspect
                                    </option>
                                    <option value="north"
                                        {{ isset($property) && $property->aspects == 'north' ? 'selected' : '' }}>North</option>
                                    <option value="south"
                                        {{ isset($property) && $property->aspects == 'south' ? 'selected' : '' }}>South</option>
                                    <option value="west"
                                        {{ isset($property) && $property->aspects == 'west' ? 'selected' : '' }}>West</option>
                                    <option value="east"
                                        {{ isset($property) && $property->aspects == 'east' ? 'selected' : '' }}>East</option>
                                    <option value="north-east"
                                        {{ isset($property) && $property->aspects == 'north-east' ? 'selected' : '' }}>North-East
                                    </option>
                                    <option value="south-east"
                                        {{ isset($property) && $property->aspects == 'south-east' ? 'selected' : '' }}>South-East
                                    </option>
                                    <option value="south-west"
                                        {{ isset($property) && $property->aspects == 'south-west' ? 'selected' : '' }}>South-West
                                    </option>
                                    <option value="north-west"
                                        {{ isset($property) && $property->aspects == 'north-west' ? 'selected' : '' }}>North-West
                                    </option>
                                </select>
                                @error('aspects')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>{{-- form-group end --}}
            
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Collecting Rent </div>
                        <div class="rs_radio_btns">
                            <div class="flex gap-5">
                                <div>
                                    <input type="radio" name="collecting_rent" id="collecting_rent_no" value="0"
                                        {{ isset($property) && $property->collecting_rent == '0' ? 'checked' : '' }} required />
                                    <label for="collecting_rent_no"> No</label>
                                </div>
                                <div>
                                    <input type="radio" name="collecting_rent" id="collecting_rent_yes" value="1"
                                        {{ isset($property) && $property->collecting_rent == '1' ? 'checked' : '' }} required />
                                    <label for="collecting_rent_yes"> Yes</label>
                                </div>
                            </div>
                            @error('collecting_rent')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>{{-- form-group end --}}
            
            
                    <div class="form-group">
                        <div class="accordion_inner_heading mt-4 mb-2">Area </div>
                        <div class="grid-auto-fit">
                            <div class="form-group">
                                <label>Square Feet</label>
                                <input type="number" name="square_feet" step="0.0001" class="form-control" placeholder="Square Feet"
                                    value="{{ isset($property) && $property->square_feet != '' ? number_format($property->square_feet, 2, '.', '') : '' }}"
                                    required>
                                @error('square_feet')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label>Square Meter</label>
                                <input type="number" name="square_meter" step="0.0001" class="form-control" placeholder="Square Meter"
                                    value="{{ isset($property) && $property->square_meter != '' ? number_format($property->square_meter, 2, '.', '') : '' }}"
                                    required>
                                @error('square_meter')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>{{-- grid-auto-fit end --}}
                    </div>{{-- form-group end --}}
                    
                    <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>



        </div>
    </form>

<script>
    // Event listener for the Square Feet input
    document.querySelector('input[name="square_feet"]').addEventListener('input', function() {
        var squareFeet = parseFloat(this.value);
        if (!isNaN(squareFeet)) {
            var squareMeter = squareFeet * 0.09290303997; // Conversion factor from square feet to square meters
            document.querySelector('input[name="square_meter"]').value = squareMeter.toFixed(2);
        }
    });

    // Event listener for the Square Meter input
    document.querySelector('input[name="square_meter"]').addEventListener('input', function() {
        var squareMeter = parseFloat(this.value);
        if (!isNaN(squareMeter)) {
            var squareFeet = squareMeter * 10.7639104167; // Conversion factor from square meters to square feet
            document.querySelector('input[name="square_feet"]').value = squareFeet.toFixed(2);
        }
    });
</script>

    
@endif
