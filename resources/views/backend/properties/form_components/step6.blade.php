@php
    $currentStep = 6 ;
    $stations = isset($property->nearest_station) ? $property->nearest_station : '';
    $schools = isset($property->nearest_school) ? $property->nearest_school : '';
@endphp
<!-- resources/views/backend/properties/form_components/step6.blade.php -->


<form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST"
    action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id"
        value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Accessiblity</label>

    <div class="property-form-data-attribute" data-step-name="Accessiblity" data-step-number="{{ $currentStep }}"
        data-step-title="Accessiblity"></div>

    <div class="row h_100_vh">
        <div class="col-lg-6 col-12">

            <div class="steps_wrapper">

                {{-- <div class="form-group">
                    <x-backend.input-comp class="" inputOpt="input_custom_icon" inputType="text" rightIcon=""
                        inputName="school_name" placeHolder="School Name" isLabel={{ false }}
                        label="Nearest School" isDate={{ false }} isIcon={{ true }} iconName="bi-plus"
                        onIconClick="onIconClick" />
                    @error('school_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <ul class="input_list_items">
                        <li><span>Hampton High</span> <i class="bi bi-x-lg x_icon"></i> </li>
                        <li><span>Tower House School</span> <i class="bi bi-x-lg x_icon"></i></li>
                    </ul>
                </div> --}}
                {{-- <div class="form-group">
                    <x-backend.input-comp class="" inputOpt="input_custom_icon" inputType="text" rightIcon=""
                        inputName="relegious_places" placeHolder="Relegious Places" isLabel={{ false }}
                        label="Nearest Relegious Places" isDate={{ false }} isIcon={{ true }}
                        iconName="bi-plus" onIconClick="onIconClick" />
                    @error('relegious_places')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <ul class="input_list_items">
                        <li><span>Tonbridge Street</span> <i class="bi bi-x-lg x_icon"></i> </li>
                        <li><span>East Sheen</span> <i class="bi bi-x-lg x_icon"></i></li>
                    </ul>
                </div> --}}
                <div class="form-group">
                    <div class="rs_sub_title">Access Arrangement</div>
                    <textarea name="access_arrangement" required>{{ isset($property) && $property->access_arrangement ? $property->access_arrangement : '' }}</textarea>
                    @error('access_arrangement')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="rs_sub_title">Key Highlights</div>
                    <textarea name="key_highlights" required>{{ isset($property) && $property->key_highlights ? $property->key_highlights : '' }}</textarea>
                    @error('key_highlights')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                {{-- <!-- Nearest Station -->
                <div class="form-group">
                    <div class="rs_sub_title">Nearest Station</div>
                    @php
                        // If nearest_station is not null, decode the comma-separated string into an array
                        $stations = isset($property->nearest_station) ? $property->nearest_station : '';
                    @endphp
                    <input id="station_name" type="text" class="tagify-input" placeholder="Station Name">
                    <input type="hidden" name="nearest_station" value="{{ $stations }}" class="hidden-input" required>
                    @error('nearest_station')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Nearest School -->
                <div class="form-group">
                    <div class="rs_sub_title">Nearest School</div>
                    @php
                        // Get nearest_school as comma-separated IDs
                        $schools = isset($property->nearest_school) ? $property->nearest_school : '';
                    @endphp
                    <input id="school_name" type="text" class="tagify-input" placeholder="School Name">
                    <input type="hidden" name="nearest_school" value="{{ $schools }}" class="hidden-input" required>
                    @error('nearest_school')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div> --}}

                <!-- Nearest Station -->
                <div class="form-group">
                    <div class="rs_sub_title">Nearest Station</div>
                    <input id="station_name" type="text" class="tagify-input" placeholder="Station Name"
                           data-source="stations"
                           data-values="{{ $stations }}"
                           data-id-value="{{ json_encode($allstations) }}"
                           data-options="{{ json_encode(['maxTags' => 5, 'dropdownEnabled' => 1, 'maxItems' => 10, 'searchKeys' => ['name'], 'closeOnSelect' => false])  }}">
                    <input type="hidden" name="nearest_station" value="{{ $stations }}" class="hidden-input" required>
                    @error('nearest_station')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Nearest School -->
                <div class="form-group">
                    <div class="rs_sub_title">Nearest School</div>
                    <input id="school_name" type="text" class="tagify-input" placeholder="School Name"
                           data-source="schools"
                           data-values="{{ $schools }}"
                           data-id-value="{{ json_encode($allschools) }}"
                           data-options="{{ json_encode(['maxTags' => 5, 'dropdownEnabled' => 1, 'maxItems' => 10, 'searchKeys' => ['name'], 'closeOnSelect' => false])  }}">
                    <input type="hidden" name="nearest_school" value="{{ $schools }}" class="hidden-input" required>
                    @error('nearest_school')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="form-group">
                    <div class="rs_sub_title">Nearest Religious Places (Distance in KM)</div>
                    @php
                        // If nearest_religious_places is not null, decode the JSON and use it; otherwise, use empty values
                        $religiousPlaces =
                            isset($property) && $property->nearest_religious_places
                                ? json_decode($property->nearest_religious_places)
                                : ['masjid' => '', 'church' => '', 'mandir' => ''];
                    @endphp
                    <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                        name="nearest_religious_places[masjid]" value="{{ $religiousPlaces->masjid ?? '' }}"
                        placeholder="Masjid" required>
                    <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                        name="nearest_religious_places[church]" value="{{ $religiousPlaces->church ?? '' }}"
                        placeholder="Church" required>
                    <input type="number" pattern="[0-9]" class="form-control mt-2" inputmode="numeric"
                        name="nearest_religious_places[mandir]" value="{{ $religiousPlaces->mandir ?? '' }}"
                        placeholder="Mandir" required>
                    @error('nearest_religious_places')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="form-group">
                    <label for="useful_information">Useful Information</label>
                    <input type="text" name="useful_information" id="useful_information" class="form-control"
                        value="{{ isset($property) && $property->useful_information ? $property->useful_information : '' }}"
                        required>
                    @error('useful_information')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="footer_btn">
                    <div class="row">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary w-100 previous-step"
                                data-previous-step="{{ $currentStep - 1 }}"
                                data-current-step="{{ $currentStep }}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn_secondary w-100 next-step"
                                data-next-step="{{ $currentStep + 1 }}"
                                data-current-step="{{ $currentStep }}">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
