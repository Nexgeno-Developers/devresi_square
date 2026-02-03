@php $currentStep = 2 ; @endphp
<!-- resources/views/backend/properties/form_components/step2.blade.php -->
<form id="property-form-step-{{ $currentStep }}" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <div class="property-form-data-attribute" data-step-name="Property Type" data-step-number="{{ $currentStep }}" data-step-title="Property Type"></div>
    <label class="main_title">Property Type</label>
    <div class="steps_wrapper">
        <div class="row h_100_vh">
            <div class="col-lg-6 col-12">
                <div class="flex gap_24 flex_col">
                    <div class="form-group pt_wrapper">
                        <div class="rs_radio_btns">
                            <div>
                                <input type="radio" name="property_type" id="property_type_sales" value="sales" {{ (isset($property) && $property->property_type == 'sales') ? 'checked' : '' }} required /> 
                                <label for="property_type_sales" >Sales</label>
                            </div>
                            <div>
                                <input type="radio" name="property_type" id="property_type_lettings" value="lettings" {{ (isset($property) && $property->property_type == 'lettings') ? 'checked' : '' }} required /> 
                                <label for="property_type_lettings">Lettings</label>
                            </div>
                            <div>
                                <input type="radio" name="property_type" id="property_type_both" value="both" {{ (isset($property) && $property->property_type == 'both') ? 'checked' : '' }} required /> 
                                <label for="property_type_both">Both</label>
                            </div>
                        </div>
                        @error('property_type')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Transaction Type</label>
                        <div class="rs_radio_btns">
                            <div>
                                <input type="radio" name="transaction_type" id="transaction_type_residential" value="residential" {{ (isset($property) && $property->transaction_type == 'residential') ? 'checked' : '' }} required /> 
                                <label for="transaction_type_residential" >Residential</label>
                            </div>
                            <div>
                                <input type="radio" name="transaction_type" id="transaction_type_commercial" value="commercial" {{ (isset($property) && $property->transaction_type == 'commercial') ? 'checked' : '' }} required />
                                <label for="transaction_type_commercial" >Commercial</label>
                            </div>
                        </div>
                        @error('transaction_type')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Specific Property Type</label>
                        <div class="rs_radio_btns">
                            <div>
                                <input type="radio" name="specific_property_type" id="specific_property_type_appartment" value="appartment" {{ (isset($property) && $property->specific_property_type == 'appartment') ? 'checked' : '' }} required> 
                                <label for="specific_property_type_appartment" >Appartment</label>
                            </div>
                            <div>
                                <input type="radio" name="specific_property_type" id="specific_property_type_flat" value="flat" {{ (isset($property) && $property->specific_property_type == 'flat') ? 'checked' : '' }} required> 
                                <label for="specific_property_type_flat" >Flat</label>
                            </div>
                            <div>
                                <input type="radio" name="specific_property_type" id="specific_property_type_bunglow" value="bunglow" {{ (isset($property) && $property->specific_property_type == 'bunglow') ? 'checked' : '' }} required> 
                                <label for="specific_property_type_bunglow" >Bunglow</label>
                            </div>
                            <div>
                                <input type="radio" name="specific_property_type" id="specific_property_type_house" value="house" {{ (isset($property) && $property->specific_property_type == 'house') ? 'checked' : '' }} required> 
                                <label for="specific_property_type_house" >House</label>
                            </div>
                        </div>
                        @error('specific_property_type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="footer_btn">
                    <div class="row mt-lg-4">
                        <div class="col-6">
                            <button type="button" class="btn btn_outline_secondary previous-step w-100" data-previous-step="{{ $currentStep - 1 }}" data-current-step="{{ $currentStep }}">Previous</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn_secondary next-step w-100" data-next-step="{{ $currentStep + 1 }}" data-current-step="{{ $currentStep }}">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>