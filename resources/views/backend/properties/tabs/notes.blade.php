{{-- <h1>Notes</h1> --}}
{{-- @include("backend.properties.popup_forms.notes_tab", ['property' => $property, 'notes' => $notes, ]) --}}

{{-- <div id="section-notes_tab-{{ $property->id }}">
    @include("backend.properties.popup_forms.notes_tab", ['property' => $property, 'notes' => $notes,])
</div> --}}

<x-backend-notes-component
    :noteable-type="get_class($property)"
    :noteable-id="$property->id"
    :note-types="$noteTypes"
    :initial-notes="$notes"
/>
