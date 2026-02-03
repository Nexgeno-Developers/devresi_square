
<x-backend-documents-component
    :documentable-type="get_class($user)"
    :documentable-id="$user->id"
    :document-types="$documentTypes"
    :initial-documents="$documents"
/>