<!-- resources/views/backend/properties/form_components/step6.blade.php -->
@php
if(isset($property)){
    $propertyType = $property->property_type ?? '';
    $propertyPrice = $property->price ?? ''; 
    $lettingPrice = $property->letting_price ?? ''; 
    $groundRent = $property->ground_rent ?? ''; 
    $serviceCharge = $property->service_charge ?? ''; 
    $annualCouncilTax = $property->annual_council_tax ?? ''; 
    $councilTaxBand = $property->council_tax_band ?? ''; 
    $tenure = $property->tenure ?? ''; 
    $lengthOfLease = $property->length_of_lease ?? ''; 
}else{
    $propertyType = '';
    $propertyPrice = ''; 
    $lettingPrice = ''; 
    $groundRent = ''; 
    $serviceCharge = ''; 
    $annualCouncilTax = ''; 
    $councilTaxBand = ''; 
    $tenure = ''; 
    $lengthOfLease = ''; 
}
@endphp

<form id="property-form-step-6" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">

    <label class="main_title">Price</label>

    <div class="property-form-data-attribute" data-step-name="Price" data-step-number="6" data-step-title="Price"></div>

    <div class="steps_wrapper">

       <!-- Listing Sale Price Input (Show only if type is sales or both) -->
       @if($propertyType == 'sales' || $propertyType == 'both')
            <div class="form-group">
                <label for="lprice">Listing Sale Price</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">£</div>
                    <input type="text" name="price" id="price" class="form-control" value="{{ $propertyPrice }}">
                </div>
                @error('price')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <!-- Letting Price Input (Show only if type is letting or both) -->
        @if($propertyType == 'lettings' || $propertyType == 'both')
            <div class="form-group">
                <label for="letting_price">Letting Price</label>
                <div class="price_input_wrapper">
                    <div class="pound_sign">£</div>
                    <input type="text" name="letting_price" id="letting_price" class="form-control" value="{{ $lettingPrice }}">
                </div>
                @error('letting_price')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="form-group">
            <label for="ground_rent">Ground Rent</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">£</div>
                <input type="text" name="ground_rent" id="ground_rent" class="form-control" value="{{ $groundRent }}">
            </div>
            @error('ground_rent')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="service_charge">Service Charge</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">£</div>
                <input type="text" name="service_charge" id="service_charge" class="form-control" value="{{ $serviceCharge }}">
            </div>
            @error('service_charge')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="annual_council_tax">Annual Council Tax</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">£</div>
                <input type="text" name="annual_council_tax" id="annual_council_tax" class="form-control" value="{{ $annualCouncilTax }}">
            </div>
            @error('annual_council_tax')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="council_tax_band">Council Tax Band</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">£</div>
                <input type="text" name="council_tax_band" id="council_tax_band" class="form-control" value="{{ $councilTaxBand }}">
            </div>
            @error('council_tax_band')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="tenure">Tenure</label>
            <select name="tenure" id="tenure" class="form-control">
                <option value="leasehold" {{ $tenure == 'leasehold' ? 'selected' : '' }}>Leasehold</option>
                <option value="leasehold2" {{ $tenure == 'leasehold2' ? 'selected' : '' }}>Leasehold 2</option>
            </select>
            @error('tenure')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="length_of_lease">Length of Lease</label>
            <input type="text" name="length_of_lease" id="length_of_lease" class="form-control" value="{{ $lengthOfLease }}">
            @error('length_of_lease')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-secondary w-100 previous-step" data-previous-step="5" data-current-step="6">Previous</button>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-primary w-100 next-step" data-next-step="7" data-current-step="6">Next</button>
        </div> 
    </div> 
</form>
