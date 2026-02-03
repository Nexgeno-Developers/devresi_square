<!-- resources/views/backend/properties/quick_form_components/step3.blade.php -->
@php $currentStep = 2 ; @endphp
<div class="container-fluid mt-4 quick_add_property">
    <div class="row property_type_wrapper">
        <div class="col-md-6 col-12 left_col">
            <div class="left_content_wrapper">
                <div class="left_content_img">
                    <i class="bi bi-building-fill"></i>
                </div>
                <div class="left_title">
                    What<br /> <span class="secondary-color">type of property</span><br /> do you have?
                </div>
            </div>
        </div>
        <div class="col-md-6 col-12 right_col">
            <form id="property-form-step-{{$currentStep}}" method="POST" action="{{ route('admin.properties.quick_store') }}">
                @csrf
                <!-- Hidden field for property ID with isset check -->
                <input type="hidden" id="property_id" class="property_id" name="property_id"
                    value="{{ (isset($property) ? $property->id : '') }}">
                <div data-step-name="Property type" data-step-number="{{$currentStep}}"></div>
                <div class="right_content_wrapper">
                    <div class="form-group">
                        <div class="radio_bts_square_icon">
                            <input type="radio" class="propertyType" name="specific_property_type"
                                id="specific_property_type_appartment" value="appartment" {{ (isset($property) && $property->specific_property_type == 'appartment') ? 'checked' : '' }} required>
                            <label for="specific_property_type_appartment">
                                <img src="{{ asset('asset/images/svg/apartment_600.svg') }}" alt="apartment">
                                Apartment
                            </label>
                            <input type="radio" class="propertyType" name="specific_property_type"
                                id="specific_property_type_flat" value="flat" {{ (isset($property) && $property->specific_property_type == 'flat') ? 'checked' : '' }} required>
                            <label for="specific_property_type_flat">
                                <img src="{{ asset('asset/images/svg/flat_600.svg') }}" alt="flat">
                                Flat
                            </label>
                            <input type="radio" class="propertyType" name="specific_property_type"
                                id="specific_property_type_bunglow" value="bunglow" {{ (isset($property) && $property->specific_property_type == 'bunglow') ? 'checked' : '' }} required>
                            <label for="specific_property_type_bunglow">
                                <img src="{{ asset('asset/images/svg/bungalow_600.svg') }}" alt="bungalow">
                                Bungalow
                            </label>
                            <input type="radio" class="propertyType" name="specific_property_type"
                                id="specific_property_type_house" value="house" {{ (isset($property) && $property->specific_property_type == 'house') ? 'checked' : '' }} required>
                            <label for="specific_property_type_house">
                                <img src="{{ asset('asset/images/svg/house_600.svg') }}" alt="house">
                                House
                            </label>
                        </div>
                        @error('reception')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
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