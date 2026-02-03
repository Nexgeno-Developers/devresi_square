@php $currentStep = 7 ; @endphp
<!-- resources/views/backend/properties/quick_form_components/step7.blade.php -->
<div class="container-fluid mt-4 quick_add_property">
    <div class="row">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="left_title">
                    Which <span class="secondary-color">Amenity</span> does<br/>
                    your property have?
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="property-form-step-{{$currentStep}}" method="POST" action="{{ route('admin.properties.quick_store') }}">
                @csrf
                <!-- Hidden field for property ID with isset check -->
                <input type="hidden" id="property_id" class="property_id" name="property_id"
                    value="{{ (isset($property) ? $property->id : '') }}">
                <div data-step-name="Property Address" data-step-number="{{$currentStep}}"></div>
                <div class="right_content_wrapper">
                    <div class="">
                        <div class="rc_title">Parking</div>
                        <div class="radio_bts_square">
                            <input required type="radio" class="parking-radio" name="parking" id="parking1"
                                value="1" {{ (isset($property) && $property->parking == '1') ? 'checked' : '' }} />
                            <label for="parking1"> Yes </label>
                            <input required type="radio" class="parking-radio" name="parking" id="parking2"
                                value="0" {{ (isset($property) && $property->parking == '0') ? 'checked' : '' }} />
                            <label for="parking2"> No </label>
                        </div>
                        <div class="form-group" id="parking_location_group" style="display: none;">
                            <label for="parking_location">Parking Location</label>
                            <input type="text" name="parking_location" class="form-control parking-location"
                                value="{{ $property->parking_location ?? '' }}" />
                            @error('parking_location')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="">
                        <div class="rc_title">Garden</div>
                        <div class="radio_bts_square">
                            <input required type="radio" class="garden-radio" name="garden" id="garden1"
                                value="1" {{ (isset($property) && $property->garden == '1') ? 'checked' : '' }} />
                            <label for="garden1"> Yes </label>
                            <input required type="radio" class="garden-radio" name="garden" id="garden2"
                                value="0" {{ (isset($property) && $property->garden == '0') ? 'checked' : '' }} />
                            <label for="garden2"> No </label>
                        </div>
                    </div>
                    <div class="">
                        <div class="rc_title">Balcony</div>
                        <div class="radio_bts_square">
                            <input required type="radio" class="balcony-radio" name="balcony" id="balcony1"
                                value="1" {{ (isset($property) && $property->balcony == '1') ? 'checked' : '' }} />
                            <label for="balcony1"> Yes </label>
                            <input required type="radio" class="balcony-radio" name="balcony" id="balcony2"
                                value="0" {{ (isset($property) && $property->balcony == '0') ? 'checked' : '' }} />
                            <label for="balcony2"> No </label>
                        </div>
                    </div>
                </div>
                <div class="d-flex d-none gap-3">
                    <button type="button" class="btn btn-secondary previous-step mt-2 w-100" data-previous-step="{{$currentStep-1}}"
                        data-current-step="{{$currentStep}}">Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step mt-2 w-100" data-next-step="{{$currentStep+1}}"
                        data-current-step="{{$currentStep}}">Next</button>
                </div>
            </form>
        </div>
    </div>
</div>
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