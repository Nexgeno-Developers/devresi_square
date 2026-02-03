<!-- resources/views/backend/properties/form_components/step3.blade.php -->
<form id="property-form-step-3" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Property Information</label>

    <div class="property-form-data-attribute" data-step-name="Property Information" data-step-number="3" data-step-title="Property Information"></div>
    <div class="steps_wrapper">
        <div class="form-group">
            <label>Bedrooms</label>
            <div class="radio_bts_square">
                <input type="radio" name="bedroom" id="bedroomStudio" value="studio" {{ (isset($property) && $property->bedroom == 'studio') ? 'checked' : '' }} required> <label for="bedroomStudio">Studio </label>
                <input type="radio" name="bedroom" id="bedroom1" value="1" {{ (isset($property) && $property->bedroom == '1') ? 'checked' : '' }} required> <label for="bedroom1">1 </label>
                <input type="radio" name="bedroom" id="bedroom2" value="2" {{ (isset($property) && $property->bedroom == '2') ? 'checked' : '' }} required> <label for="bedroom2">2 </label>
                <input type="radio" name="bedroom" id="bedroom3" value="3" {{ (isset($property) && $property->bedroom == '3') ? 'checked' : '' }} required> <label for="bedroom3">3 </label>
                <input type="radio" name="bedroom" id="bedroom4" value="4" {{ (isset($property) && $property->bedroom == '4') ? 'checked' : '' }} required> <label for="bedroom4">4 </label>
                <input type="radio" name="bedroom" id="bedroom5" value="5" {{ (isset($property) && $property->bedroom == '5') ? 'checked' : '' }} required> <label for="bedroom5">5 </label>
                <input type="radio" name="bedroom" id="bedroom6" value="6+" {{ (isset($property) && $property->bedroom == '6+') ? 'checked' : '' }} required> <label for="bedroom6">6+ </label>
            </div>
            @error('bedroom')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label>Bathrooms</label>
            <div class="radio_bts_square">
                <input type="radio" name="bathroom" id="bathroom1" value="1" {{ (isset($property) && $property->bathroom == '1') ? 'checked' : '' }} required> <label for="bathroom1">1 </label>
                <input type="radio" name="bathroom" id="bathroom2" value="2" {{ (isset($property) && $property->bathroom == '2') ? 'checked' : '' }} required> <label for="bathroom2">2 </label>
                <input type="radio" name="bathroom" id="bathroom3" value="3" {{ (isset($property) && $property->bathroom == '3') ? 'checked' : '' }} required> <label for="bathroom3">3 </label>
                <input type="radio" name="bathroom" id="bathroom4" value="4" {{ (isset($property) && $property->bathroom == '4') ? 'checked' : '' }} required> <label for="bathroom4">4 </label>
                <input type="radio" name="bathroom" id="bathroom5" value="5" {{ (isset($property) && $property->bathroom == '5') ? 'checked' : '' }} required> <label for="bathroom5">5</label>
                <input type="radio" name="bathroom" id="bathroom6" value="6+" {{ (isset($property) && $property->bathroom == '6+') ? 'checked' : '' }} required> <label for="bathroom6">6+ </label>
            </div>
            @error('bathroom')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label>Reception Rooms</label>
            <div class="radio_bts_square">
                <input type="radio" name="reception" id="reception1" value="1" {{ (isset($property) && $property->reception == '1') ? 'checked' : '' }} required /> 
                <label for="reception1"> 1 </label>
                <input type="radio" name="reception" id="reception2" value="2" {{ (isset($property) && $property->reception == '2') ? 'checked' : '' }} required /> 
                <label for="reception2"> 2 </label>
                <input type="radio" name="reception" id="reception3" value="3" {{ (isset($property) && $property->reception == '3') ? 'checked' : '' }} required /> 
                <label for="reception3"> 3 </label>
                <input type="radio" name="reception" id="reception4" value="4" {{ (isset($property) && $property->reception == '4') ? 'checked' : '' }} required /> 
                <label for="reception4"> 4 </label>
                <input type="radio" name="reception" id="reception5" value="5" {{ (isset($property) && $property->reception == '5') ? 'checked' : '' }} required /> 
                <label for="reception5"> 5 </label>
                <input type="radio" name="reception" id="reception6" value="6+" {{ (isset($property) && $property->reception == '6+') ? 'checked' : '' }} required /> 
                <label for="reception6">6+</label>
            </div>
            @error('reception')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label>Parking</label>
            <div class="rs_radio_btns">
                <div>
                    <input type="radio" name="parking" id="parking_no" value="0" {{ (isset($property) && $property->parking == '0') ? 'checked' : '' }} required /> 
                    <label for="parking_no" > No</label>
                </div>
                <div>
                    <input type="radio" name="parking" id="parking_yes" value="1" {{ (isset($property) && $property->parking == '1') ? 'checked' : '' }} required /> 
                    <label for="parking_yes" > Yes</label>
                </div>
                @error('parking')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label>Balcony</label>
            <div class="rs_radio_btns">
                <div>
                    <input type="radio" name="balcony" id="balcony_no" value="0" {{ (isset($property) && $property->balcony == '0') ? 'checked' : '' }} required /> 
                    <label for="balcony_no" > No</label>
                </div>
                <div>
                    <input type="radio" name="balcony" id="balcony_yes" value="1" {{ (isset($property) && $property->balcony == '1') ? 'checked' : '' }} required /> 
                    <label for="balcony_yes" > Yes</label>
                </div>
                @error('balcony')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            
        </div>

        <div class="form-group">
            <label>Garden</label>
            <div class="rs_radio_btns">
                <div>
                    <input type="radio" name="garden" id="garden_no" value="0" {{ (isset($property) && $property->garden == '0') ? 'checked' : '' }} required /> 
                    <label for="garden_no" > No</label>
                </div>
                <div>
                    <input type="radio" name="garden" id="garden_yes" value="1" {{ (isset($property) && $property->garden == '1') ? 'checked' : '' }} required /> 
                    <label for="garden_yes" > Yes</label>
                </div>
                @error('garden')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label>Service</label>
            <select name="service" class="form-control" required>
                <option value="" disabled {{ (isset($property) && $property->service == '') ? 'selected' : '' }}>Select a service</option>
                <option value="fully manged" {{ (isset($property) && $property->service == 'fully manged') ? 'selected' : '' }}>Fully Manged</option>
                <option value="let and rent collect" {{ (isset($property) && $property->service == 'let and rent collect') ? 'selected' : '' }}>Let And Rent Collect</option>
            </select>
            @error('service')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Collecting Rent</label>
            <div class="rs_radio_btns">
                <div>
                    <input type="radio" name="collecting_rent" id="collecting_rent_no" value="0" {{ (isset($property) && $property->collecting_rent == '0') ? 'checked' : '' }} required /> 
                    <label for="collecting_rent_no" > No</label>
                </div>
                <div>
                    <input type="radio" name="collecting_rent" id="collecting_rent_yes" value="1" {{ (isset($property) && $property->collecting_rent == '1')  ? 'checked' : '' }} required /> 
                    <label for="collecting_rent_yes" > Yes</label>
                </div>
                @error('collecting_rent')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label>Floor</label>
            <select name="floor" class="form-control" required>
                <option value="" disabled {{ (isset($property) && $property->floor == '') ? 'selected' : '' }}>Select a floor</option>
                <option value="furnished " {{ (isset($property) && $property->floor == 'furnished') ? 'selected' : '' }}>Furnished</option>
                <option value="unfurnished" {{ (isset($property) && $property->floor == 'unfurnished') ? 'selected' : '' }}>Unfurnished</option>
            </select>
            @error('floor')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Area</label>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>Square Feet</label>
                        <input type="number" name="square_feet" class="form-control" placeholder="Square Feet" value="{{ (isset($property) && $property->square_feet != '') ? $property->square_feet : ''  }}" required>
                        @error('square_feet')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Square Meter</label>
                        <input type="number" name="square_meter" class="form-control" placeholder="Square Meter" value="{{ (isset($property) && $property->square_meter != '') ? $property->square_meter : ''  }}" required>
                        @error('square_meter')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        

        <div class="form-group">
            <label>Aspects</label>
            <select name="aspects" class="form-control" required>
                <option value="" disabled {{ (isset($property) && $property->aspects == '') ? 'selected' : ''  }}>Select an aspect</option>
                <option value="north" {{ (isset($property) && $property->aspects == 'north') ? 'selected' : ''  }}>North</option>
                <option value="south" {{ (isset($property) && $property->aspects == 'south') ? 'selected' : ''  }}>South</option>
                <option value="west" {{ (isset($property) && $property->aspects == 'west') ? 'selected' : ''  }}>West</option>
                <option value="east" {{ (isset($property) && $property->aspects == 'east') ? 'selected' : ''  }}>East</option>
            </select>
            @error('aspects')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>    
        <div class="row">
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-secondary w-100 previous-step w-100" data-previous-step="2" data-current-step="3">Previous</button>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-primary w-100 next-step w-100" data-next-step="4" data-current-step="3">Next</button>
            </div>
        </div>    
    </div>
</form>
<script>
    // Event listener for the Square Feet input
    document.querySelector('input[name="square_feet"]').addEventListener('input', function() {
        var squareFeet = parseFloat(this.value);
        if (!isNaN(squareFeet)) {
            var squareMeter = squareFeet * 0.09290303997; // Conversion factor from square feet to square meters
            document.querySelector('input[name="square_meter"]').value = squareMeter.toFixed(4);
        }
    });

    // Event listener for the Square Meter input
    document.querySelector('input[name="square_meter"]').addEventListener('input', function() {
        var squareMeter = parseFloat(this.value);
        if (!isNaN(squareMeter)) {
            var squareFeet = squareMeter * 10.7639104167; // Conversion factor from square meters to square feet
            document.querySelector('input[name="square_feet"]').value = squareFeet.toFixed(4);
        }
    });

</script>