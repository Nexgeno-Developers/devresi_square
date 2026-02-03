
<x-backend-notes-component
    :noteable-type="get_class($user)"
    :noteable-id="$user->id"
    :note-types="$noteTypes"
    :initial-notes="$notes"
/>