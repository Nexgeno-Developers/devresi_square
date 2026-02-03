@php
$petsAllowed = booleanToYesNo($property->pets_allow) ?? '';
@endphp
@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    <div class="accordion_inner">
        <div class="accordion_services_item">
            <div>
                <span class="left_item">Parking:</span> 
                {{ $property->parking == '1' ? 'Yes' : 'No' }}
            </div>
            <div>
                @if($property->parking == '1' && !empty($property->parking_location))
                <span class="left_item">Parking Location:</span> {{ $property->parking_location }}
                @endif
            </div>
            <div>
                <span class="left_item">Service:</span> {{ $property->service ?? 'N/A' }}
            </div>
            <div>
                @if(isset($property) && $property->property_type == 'lettings' || $property->property_type == 'both')
                <span class="left_item">Pets Allow?:</span> {{ $petsAllowed }}
                @endif
            </div>
        </div>
    </div>


@else
    <form id="propertyServiceForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_services">

        <div class="form-group">
            <label>Parking</label>
            <div class="radio_bts_square">
                <input required type="radio" class="parking-radio" name="parking" id="parking1" value="0"
                    {{ (isset($property) && $property->parking == '0') ? 'checked' : '' }} />
                <label for="parking1"> No </label>
                <input required type="radio" class="parking-radio" name="parking" id="parking2" value="1"
                    {{ (isset($property) && $property->parking == '1') ? 'checked' : '' }} />
                <label for="parking2"> Yes </label>
            </div>
            @error('parking')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-lg-6 col-12">
                <div class="form-group" id="parking_location_group" style="display: none;">
                    <label for="parking_location">Parking Location</label>
                    <input type="text" name="parking_location" class="form-control"
                        value="{{ $property->parking_location ?? '' }}" />
                    @error('parking_location')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Service</label>
            <div class="row">
                <div class="col-lg-6 col-12">
                    <select name="service" class="form-control" required>
                        <option value="" disabled {{ (isset($property) && $property->service == '') ? 'selected' : '' }}>
                            Select a service</option>
                        <option value="Comprehensive Management" {{ (isset($property) && $property->service == 'Comprehensive Management') ? 'selected' : '' }}>Comprehensive Management </option>
                        <option value="Standard Management" {{ (isset($property) && $property->service == 'Standard Management') ? 'selected' : '' }}>Standard Management</option>
                        <option value="fully manged" {{ (isset($property) && $property->service == 'fully manged') ? 'selected' : '' }}>Fully Manged</option>
                        <option value="let and rent collect" {{ (isset($property) && $property->service == 'let and rent collect') ? 'selected' : '' }}>Let And Rent Collect</option>
                        <option value="let only" {{ (isset($property) && $property->service == 'let only') ? 'selected' : '' }}>Let Only</option>
                    </select>
                    @error('service')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @if(isset($property) && $property->property_type == 'lettings' || $property->property_type == 'both')
            <div class="form-group">
                <input type="checkbox" name="pets_allow" id="pets_allow" style="width: 5%;"
                    value="{{ isset($property) && $property->pets_allow == 1 ? 1 : 0 }}" {{ isset($property) && $property->pets_allow == 1 ? 'checked' : '' }} />
                <label for="pets_allow">Pets Allowed</label>
            </div>
        @endif

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>

    <script>
        function initializeParkingRadios() {
            // Declare the variable for radio buttons if it's not already declared
            let parkingRadios = document.querySelectorAll('input[name="parking"]');
            const parkingLocationGroup = document.getElementById('parking_location_group');

            // Add event listener to handle changes
            parkingRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    // Show or hide the parking location group based on the selected value
                    parkingLocationGroup.style.display = (radio.value === '1') ? 'block' : 'none';
                });
            });

            // Check if any radio button is already selected, and show the field accordingly
            const selectedRadio = Array.from(parkingRadios).find(radio => radio.checked);
            if (selectedRadio && selectedRadio.value === '1') {
                parkingLocationGroup.style.display = 'block'; // Show the field if selected value is '1'
            } else {
                parkingLocationGroup.style.display = 'none';  // Hide the field if '0' or none selected
            }
        }

        // Call the function to initialize
        initializeParkingRadios();

    </script>
@endif