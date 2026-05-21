@php
    $salesStatusDescription = $property->sales_status_description ?? '';
    $lettingStatusDescription = $property->letting_status_description ?? '';
@endphp

@if(!isset($editMode) || !$editMode)
    @if($salesStatusDescription)
        <div class="mb-2">
            <strong>Sales:</strong>
            <x-toggle-description :text="$salesStatusDescription" :limit="180" />
        </div>
    @endif

    @if($lettingStatusDescription)
        <div>
            <strong>Lettings:</strong>
            <x-toggle-description :text="$lettingStatusDescription" :limit="180" />
        </div>
    @endif

    @if(!$salesStatusDescription && !$lettingStatusDescription)
        <span class="text-muted">No description added.</span>
    @endif
@else
    <form id="propertyDescriptionForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="property_description">

        <div class="form-group">
            <label for="sales_status_description">Sales Description</label>
            <textarea name="sales_status_description" id="sales_status_description" rows="6" class="form-control">{{ $salesStatusDescription }}</textarea>
        </div>

        <div class="form-group">
            <label for="letting_status_description">Letting Description</label>
            <textarea name="letting_status_description" id="letting_status_description" rows="6" class="form-control">{{ $lettingStatusDescription }}</textarea>
        </div>

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif
