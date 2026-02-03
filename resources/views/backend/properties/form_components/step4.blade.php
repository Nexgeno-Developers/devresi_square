@php $currentStep = 4 ; @endphp
<!-- resources/views/backend/properties/form_components/step4.blade.php -->
<form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id"
        value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Current Status</label>

    <div class="property-form-data-attribute" data-step-name="Current Status" data-step-number="{{ $currentStep }}"
        data-step-title="Current Status"></div>

    <div class="steps_wrapper">
        <div class="row h_100_vh">
            <div class="col-lg-5 col-12">
                @if(isset($property) && ($property->property_type == 'sales' || $property->property_type == 'both'))
                    <div class="form-group">
                        <label for="sales_current_status">Sales Status</label>
                        <select name="sales_current_status" id="sales_current_status" class="form-control" required>
                            <option value="" disabled {{ (isset($property) && $property->sales_current_status == '') ? 'selected' : ''  }}>Select a Status</option>
                            <option value="for sale" {{ (isset($property) && $property->sales_current_status == 'for sale') ? 'selected' : '' }}>For Sale</option>
                            <option value="on hold" {{ (isset($property) && $property->sales_current_status == 'on hold') ? 'selected' : '' }}>On Hold</option>
                            <option value="under offer" {{ (isset($property) && $property->sales_current_status == 'under offer') ? 'selected' : '' }}>Under Offer</option>
                            <option value="sold" {{ (isset($property) && $property->sales_current_status == 'sold') ? 'selected' : '' }}>Sold</option>
                            <option value="sold STC" {{ (isset($property) && $property->sales_current_status == 'sold STC') ? 'selected' : '' }}>Sold STC</option>
                            <option value="sold by other" {{ (isset($property) && $property->sales_current_status == 'sold by other') ? 'selected' : '' }}>Sold By Other</option>
                            <option value="exchanged" {{ (isset($property) && $property->sales_current_status == 'exchanged') ? 'selected' : '' }}>Exchanged</option>
                            <option value="available" {{ (isset($property) && $property->sales_current_status == 'available') ? 'selected' : '' }}>Available</option>
                            <option value="let agreed" {{ (isset($property) && $property->sales_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                        </select>
                        @error('sales_current_status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if(isset($property) && ($property->property_type == 'lettings' || $property->property_type == 'both'))
                    <div class="form-group">
                        <label for="letting_current_status">Letting Status</label>
                        <select name="letting_current_status" id="letting_current_status" class="form-control" required>
                            <option value="" disabled {{ (isset($property) && $property->letting_current_status == '') ? 'selected' : ''  }}>Select a Status</option>
                            <option value="not available" {{ (isset($property) && $property->letting_current_status == 'not available') ? 'selected' : '' }}>Not Available</option>
                            <option value="available" {{ (isset($property) && $property->letting_current_status == 'available') ? 'selected' : '' }}>Available</option>
                            <option value="let agreed" {{ (isset($property) && $property->letting_current_status == 'let agreed') ? 'selected' : '' }}>Let Agreed</option>
                            <option value="let by other" {{ (isset($property) && $property->letting_current_status == 'let by other') ? 'selected' : '' }}>Let By Other</option>
                        </select>
                        @error('letting_current_status')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label for="sales_status_description">Sales Description</label>
                    <textarea name="sales_status_description" id="sales_status_description" rows="6" class="form-control">{{ isset($property) && $property->sales_status_description ? $property->sales_status_description : '' }}</textarea>
                </div>

                <div class="form-group">
                    <label for="letting_status_description">Letting Description</label>
                    <textarea name="letting_status_description" id="letting_status_description" rows="6" class="form-control">{{ isset($property) && $property->letting_status_description ? $property->letting_status_description : '' }}</textarea>
                </div>

                <div class="form-group">
                    <label for="available_from">Date of Availability</label>
                    <input type="date" name="available_from" id="available_from" class="form-control"
                        value="{{ isset($property) && $property->available_from ? $property->available_from : '' }}">
                    @error('available_from')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                @if(isset($property) && $property->property_type == 'lettings' || $property->property_type == 'both')
                    <div class="form-group">
                        <input type="checkbox" name="pets_allow" id="pets_allow" style="width: 5%;" value="{{ isset($property) && $property->pets_allow == 1 ? 1 : 0 }}" {{ isset($property) && $property->pets_allow == 1 ? 'checked' : '' }} />
                        <label for="pets_allow">Pets Allowed</label>
                    </div>
                @endif

                <div class="form-group">
                    <label for="market_on">Market On</label>
                    <select name="market_on[]" id="market_on" class="form-control select2" multiple>
                        <option value="resisquare" {{ (isset($property) && is_array($property->market_on) && in_array('resisquare', $property->market_on)) || (is_array(old('market_on')) && in_array('resisquare', old('market_on'))) ? 'selected' : '' }}>Resisquare</option>
                        <option value="rightmove" {{ (isset($property) && is_array($property->market_on) && in_array('rightmove', $property->market_on)) || (is_array(old('market_on')) && in_array('rightmove', old('market_on'))) ? 'selected' : '' }}>Rightmove</option>
                        <option value="zoopla" {{ (isset($property) && is_array($property->market_on) && in_array('zoopla', $property->market_on)) || (is_array(old('market_on')) && in_array('zoopla', old('market_on'))) ? 'selected' : '' }}>Zoopla</option>
                        <option value="onthemarket" {{ (isset($property) && is_array($property->market_on) && in_array('onthemarket', $property->market_on)) || (is_array(old('market_on')) && in_array('onthemarket', old('market_on'))) ? 'selected' : '' }}>OnTheMarket</option>
                    </select>
                    @error('market_on')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="footer_btn">
                    <div class="row mt-lg-4">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary w-100 previous-step" data-previous-step="{{ $currentStep -1 }}"
                                data-current-step="{{ $currentStep }}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn_secondary w-100 next-step" data-next-step="{{ $currentStep + 1}}"
                                data-current-step="{{ $currentStep }}">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<script>

// jQuery to handle setting the value
$(document).ready(function() {
    $('#pets_allow').change(function() {
        // Set the value to 1 if checked, otherwise set to 0
        this.value = this.checked ? 1 : 0;
    });
});

// JavaScript to handle setting the value
// document.addEventListener('DOMContentLoaded', function() {
//     var petsAllowCheckbox = document.getElementById('pets_allow');

//     petsAllowCheckbox.addEventListener('change', function() {
//         // Set the value to 1 if checked, otherwise set to 0
//         petsAllowCheckbox.value = petsAllowCheckbox.checked ? 1 : 0;
//     });
// });

</script>
