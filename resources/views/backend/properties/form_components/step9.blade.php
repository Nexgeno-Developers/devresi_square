<!-- resources/views/backend/properties/form_components/step9.blade.php -->
<form id="property-form-step-9" class="rs_steps" method="POST" action="{{ route('admin.properties.store') }}">
    @csrf
    <!-- Hidden field for property ID with isset check -->
    <input type="hidden" id="property_id" class="property_id" name="property_id" value="{{ session('property_id') ?? (isset($property) ? $property->id : '') }}">
    <input type="hidden" name="step" value="9">

    <label class="main_title">Responsibility</label>

    <div class="steps_wrapper property-form-data-attribute" data-step-name="Responsibility" data-step-number="9" data-step-title="Responsibility">


        <div class="form-group">
            <label for="designation">Designation</label>
            <select name="designation" id="designation" class="form-control" required>
                <option value="" disabled {{ (isset($property) && $property->designation == '') ? 'selected' : '' }}>Select a service</option>
                <option value="estate_agent" {{ (isset($property) && $property->designation == 'estate_agent') ? 'selected' : '' }}>Estate Agent</option>
                <option value="landlord" {{ (isset($property) && $property->designation == 'landlord') ? 'selected' : '' }}>Landlord</option>
                <option value="tenant" {{ (isset($property) && $property->designation == 'tenant') ? 'selected' : '' }}>Tenant</option>
                <option value="manager" {{ (isset($property) && $property->designation == 'manager') ? 'selected' : '' }}>Manager</option>
            </select>
            @error('designation')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="branch">Branch</label>
            <select name="branch" id="branch" class="form-control" required>
                <option value="" disabled {{ (isset($property) && $property->branch == '') ? 'selected' : '' }}>Select a service</option>
                <option value="branch1" {{ (isset($property) && $property->branch == 'branch1') ? 'selected' : '' }}>Branch1</option>
                <option value="branch2" {{ (isset($property) && $property->branch == 'branch2') ? 'selected' : '' }}>Branch2</option>
                <option value="branch3" {{ (isset($property) && $property->branch == 'branch3') ? 'selected' : '' }}>Branch3</option>
                <option value="branch4" {{ (isset($property) && $property->branch == 'branch4') ? 'selected' : '' }}>Branch4</option>
            </select>
            @error('branch')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="commission_percentage">Commission (%)</label>
            <input required type="text" name="commission_percentage" id="commission_percentage" class="form-control" value="{{ (isset($property) && $property->commission_percentage) ? $property->commission_percentage : '' }}">
            @error('commission_percentage')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="commission_amount">Commission (£)</label>
            <div class="price_input_wrapper">
                <div class="pound_sign">£</div>
                <input required type="text" name="commission_amount" id="commission_amount" class="form-control" value="{{ (isset($property) && $property->commission_amount) ? $property->commission_amount : '' }}">
            </div>
            @error('commission_amount')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-secondary w-100 previous-step" data-previous-step="8" data-current-step="9">Previous</button>
            </div>
            <div class="col-12 col-md-6">
                <button type="submit" class="btn btn-primary w-100 last-step-submit" data-current-step="9">Submit</button>
        </div>
    </div>

</form>
