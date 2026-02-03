@php $currentStep = 1 ; @endphp
<!-- resources/views/backend/properties/form_components/step1.blade.php -->
<div class="row">
<div class="col-md-5">

    <form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">
    <label class="main_title">Property Address</label>

    <div class="form-group">
        <label for="address_search">Search Property Address</label>
        <input type="text" id="address_search" class="form-control" placeholder="Enter UK property address">
    </div>

    <div class="steps_wrapper">
        <div class="property-form-data-attribute" data-step-name="Property Address" data-step-number="{{ $currentStep }}"
            data-step-title="Property address"></div>
        <div class="form-group">
            <label for="prop_name">Property Name</label>
            <input required type="text" name="prop_name" id="prop_name" class="form-control"
                value="{{ (isset($property) && $property->prop_name) ? $property->prop_name : '' }}">
            @error('prop_name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label for="line_1">Address Line 1</label>
            <input required type="text" name="line_1" id="line_1" class="form-control" value="{{ (isset($property) && $property->line_1) ? $property->line_1 : '' }}">
            @error('line_1')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label for="line_2">Address Line 2</label>
            <input type="text" name="line_2" id="line_2" class="form-control" value="{{ (isset($property) && $property->line_2) ? $property->line_2 : '' }}">
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input required type="text" name="city" id="city" class="form-control" value="{{ (isset($property) && $property->city) ? $property->city : '' }}">
            @error('city')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        <div class="row">
            <div class="form-group col-lg-8 col-12">
                <label for="country">Country</label>
                <input required type="text" name="country" id="country" class="form-control" value="{{ (isset($property) && $property->country) ? $property->country : '' }}">
                @error('country')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group col-lg-4 col-12">
                <label for="postcode">Postcode</label>
                <input required type="text" name="postcode" id="postcode" class="form-control"
                    value="{{ (isset($property) && $property->postcode) ? $property->postcode : '' }}">
                @error('postcode')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="footer_btn">
            <button type="button" class="btn btn_secondary btn-sm next-step w-100" data-next-step="{{ $currentStep + 1 }}" data-current-step="{{ $currentStep }}">Next</button>
        </div>
    </div>
    </form>
</div>
</div>

