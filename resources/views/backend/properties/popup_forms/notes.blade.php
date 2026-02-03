@if(!isset($editMode) || !$editMode)
@php
    $impNotes = isset($property) && $property->imp_notes ? $property->imp_notes : '';
@endphp
    <!-- Display View Mode -->
    <x-toggle-description :text="$impNotes" :limit="120" />
@else
    <form id="propertyNotesForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="notes">

        <div class="form-group">
            <label for="notes">Note</label>
            <textarea name="imp_notes" id="notes" rows="6" placeholder="Notes" class="form-control">{{ isset($property) && $property->imp_notes ? $property->imp_notes : '' }}</textarea>
        </div>

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>
@endif