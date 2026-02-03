<!-- resources/views/backend/properties/form_components/step4.blade.php -->
<form id="property-form-step-4" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id"
        value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Current Status</label>

    <div class="property-form-data-attribute" data-step-name="Current Status" data-step-number="4"
        data-step-title="Current Status"></div>

    <div class="steps_wrapper">
        <div class="form-group">
            <label for="current_status">Current Status</label>
            <select name="current_status" id="current_status" class="form-control">
                <option value="for sale" {{ (isset($property) && $property->current_status == 'for sale') ? 'selected' : ''  }}>For Sale</option>
                <option value="under offer" {{ (isset($property) && $property->current_status == 'under offer') ? 'selected' : ''  }}>Under Offer</option>
                <option value="sold STC" {{ (isset($property) && $property->current_status == 'sold STC') ? 'selected' : '' }}>Sold STC</option>
                <option value="available" {{ (isset($property) && $property->current_status == 'available') ? 'selected' : ''  }}>Available</option>
                <option value="let agreed" {{ (isset($property) && $property->current_status == 'let agreed') ? 'selected' : ''  }}>Let Agreed</option>
            </select>
            @error('current_status')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="status_description">Description</label>
            <textarea name="status_description" id="status_description"
                class="form-control">{{ isset($property) && $property->status_description ? $property->status_description : '' }}</textarea>
            @error('status_description')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="available_from">Date of Availability</label>
            <input type="date" name="available_from" id="available_from" class="form-control"
                value="{{ isset($property) && $property->available_from ? $property->available_from : '' }}">
            @error('available_from')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>


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

        <div class="row">
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-secondary w-100 previous-step" data-previous-step="3"
                    data-current-step="4">Previous</button>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-primary w-100 next-step" data-next-step="5"
                    data-current-step="4">Next</button>
            </div>
        </div>

</form>