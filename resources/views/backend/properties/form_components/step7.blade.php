<!-- resources/views/backend/properties/form_components/step7.blade.php -->
@php
if(isset($property)){
    $epcRating = $property->epc_rating ?? '';
    $isGas = $property->is_gas ?? '';
}else{
    $epcRating = '';
    $isGas = '';
}
@endphp
<form id="property-form-step-7" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Valid EPC</label>

    <div class="property-form-data-attribute" data-step-name="Valid EPC" data-step-number="7" data-step-title="Valid EPC"></div>
    
    <div class="steps_wrapper">
        <div class="form-group">
            <label for="epc_rating">EPC Rating</label>
            <select name="epc_rating" id="epc_rating" class="form-control">
                <option value="A" {{ $epcRating == 'A' ? 'selected' : '' }}>A</option>
                <option value="B" {{ $epcRating == 'B' ? 'selected' : '' }}>B</option>
                <option value="C" {{ $epcRating == 'C' ? 'selected' : '' }}>C</option>
                <option value="D" {{ $epcRating == 'D' ? 'selected' : '' }}>D</option>
            </select>
            @error('epc_rating')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Does the property have gas?</label>
            <div class="rs_radio_btns">
                <div>
                    <input type="radio" name="is_gas" id="is_gas_no" value="0" {{ $isGas == '0' ? 'checked' : '' }} /> 
                    <label for="is_gas_no" > No</label>
                </div>
                <div>
                    <input type="radio" name="is_gas" id="is_gas_yes" value="1" {{ $isGas == '1' ? 'checked' : '' }} /> 
                    <label for="is_gas_yes" > Yes</label>
                </div>
                @error('is_gas')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-secondary w-100 previous-step" data-previous-step="6" data-current-step="7">Previous</button>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-primary w-100 next-step" data-next-step="8" data-current-step="7">Next</button>
        </div> 
    </div> 
</form>
