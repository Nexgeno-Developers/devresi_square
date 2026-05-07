
<x-backend.notes.notes
    :noteable-type="get_class($user)"
    :noteable-id="$user->id"
    :note-types="$noteTypes"
    :initial-notes="$notes"
/>