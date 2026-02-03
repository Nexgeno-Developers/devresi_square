<!-- resources/views/backend/properties/form_components/step5.blade.php -->
<form id="property-form-step-5" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Features</label>

    <div class="property-form-data-attribute" data-step-name="Features" data-step-number="5" data-step-title="Features"></div>
        <h3>Features</h3>

        <div class="form-group">
            <label>Furniture</label>
            @foreach(['Furnished' => 'Furnished', 'Unfurnished' => 'Unfurnished', 'Flexible' => 'Flexible'] as $key => $value)
                @php
                    // Decode the furniture field if it's a JSON string.
                    $furniture = isset($property) && is_string($property->furniture) ? json_decode($property->furniture, true) : [];
                @endphp    
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="furniture[]" value="{{ $key }}" id="furniture_{{ $key }}" 
                        {{ in_array($key, $furniture) ? 'checked' : '' }}>
                    <label class="form-check-label" for="furniture_{{ $key }}">{{ $value }}</label>
                </div>
            @endforeach
        </div>

        <div class="form-group">
    <label>Kitchen</label>
    @foreach([
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
            $kitchen = isset($property) && is_string($property->kitchen) ? json_decode($property->kitchen, true) : [];
        @endphp
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="kitchen[]" value="{{ $key }}" id="kitchen_{{ $key }}" 
                {{ in_array($key, $kitchen) ? 'checked' : '' }}>
            <label class="form-check-label" for="kitchen_{{ $key }}">{{ $value }}</label>
        </div>
    @endforeach
</div>

<div class="form-group">
    <label>Heating and Cooling</label>
    @foreach([
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
            $heatingCooling = isset($property) && is_string($property->heating_cooling) ? json_decode($property->heating_cooling, true) : [];
        @endphp
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="heating_cooling[]" value="{{ $key }}" id="heating_cooling_{{ $key }}" 
                {{ in_array($key, $heatingCooling) ? 'checked' : '' }}>
            <label class="form-check-label" for="heating_cooling_{{ $key }}">{{ $value }}</label>
        </div>
    @endforeach
</div>

<div class="form-group">
    <label>Safety</label>
    @foreach([
        'External CCTV Intruder alarm system' => 'External CCTV Intruder alarm system',
        'Smoke alarm' => 'Smoke alarm (Legal requirement)',
        'Carbon monoxide detector' => 'Carbon monoxide detector',
        'Window locks' => 'Window locks',
        'Security key lock' => 'Security key lock',
    ] as $key => $value)
        @php
            // Decode the safety field if it's a JSON string.
            $safety = isset($property) && is_string($property->safety) ? json_decode($property->safety, true) : [];
        @endphp
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="safety[]" value="{{ $key }}" id="safety_{{ $key }}" 
                {{ in_array($key, $safety) ? 'checked' : '' }}>
            <label class="form-check-label" for="safety_{{ $key }}">{{ $value }}</label>
        </div>
    @endforeach
</div>

<div class="form-group">
    <label>Other</label>
    @foreach([
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
            <input class="form-check-input" type="checkbox" name="other[]" value="{{ $key }}" id="other_{{ $key }}" 
                {{ in_array($key, $other) ? 'checked' : '' }}>
            <label class="form-check-label" for="other_{{ $key }}">{{ $value }}</label>
        </div>
    @endforeach
</div>


    <div class="row">
        <div class="col-12 col-md-6">
            <button type="button" class="btn btn-secondary w-100 previous-step" data-previous-step="4" data-current-step="5">Previous</button>
        </div>
        <div class="col-12 col-md-6">
            <button type="button" class="btn btn-primary w-100 next-step" data-next-step="6" data-current-step="5">Next</button>
    </div> 
    
    
</form>
