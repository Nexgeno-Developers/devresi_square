<h1>Documents</h1>
{{-- @php
var_dump($documents);
@endphp --}}
<x-backend-documents-component
    :documentable-type="$property ? get_class($property) : null"
    :documentable-id="$property->id"
    :document-types="$documentTypes"
    :initial-documents="$documents"
/>