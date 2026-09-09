@php
    $propertyType = strtolower(trim((string) ($property->property_type ?? '')));
    $showSalesFields = ! is_landlord_plan_user() && in_array($propertyType, ['sales', 'both'], true);
    $showLettingFields = is_landlord_plan_user() || in_array($propertyType, ['lettings', 'both'], true);
    $salesStatusDescription = $property->sales_status_description ?? '';
    $lettingStatusDescription = $property->letting_status_description ?? '';
    $hasVisibleDescription = ($showSalesFields && filled($salesStatusDescription))
        || ($showLettingFields && filled($lettingStatusDescription));
@endphp

@if(!isset($editMode) || !$editMode)
    @if($showSalesFields && $salesStatusDescription)
        <div class="mb-2">
            <strong>Sales:</strong>
            <x-toggle-description :text="$salesStatusDescription" :limit="180" />
        </div>
    @endif

    @if($showLettingFields && $lettingStatusDescription)
        <div>
            <strong>Lettings:</strong>
            <x-toggle-description :text="$lettingStatusDescription" :limit="180" />
        </div>
    @endif

    @if(!$hasVisibleDescription)
        <span class="text-muted">No description added.</span>
    @endif
@else
    <form id="propertyDescriptionForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_description">

        @if($showSalesFields)
            <div class="form-group">
                <label for="sales_status_description">Sales Description</label>
                <textarea name="sales_status_description" id="sales_status_description" rows="6" class="form-control">{{ $salesStatusDescription }}</textarea>
            </div>
        @endif

        @if($showLettingFields)
            <div class="form-group">
                <label for="letting_status_description">Letting Description</label>
                <textarea name="letting_status_description" id="letting_status_description" rows="6" class="form-control">{{ $lettingStatusDescription }}</textarea>
            </div>
        @endif

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif
