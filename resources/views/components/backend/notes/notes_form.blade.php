@props(['note' => null, 'noteTypes', 'noteable'])

<form id="notesForm" method="POST" action="{{ $note ? route('admin.notes.save', $note->id) : route('admin.notes.save') }}">
    @csrf

    <input type="hidden" name="note_id"       value="{{ $note->id ?? '' }}">
    <input type="hidden" name="noteable_type" value="{{ get_class($noteable) }}">
    <input type="hidden" name="noteable_id" value="{{ $noteable->id }}">

    <div class="mb-3">
        <div class="form-group">
            <label for="noteType" class="form-label">Type</label>
            <select name="note_type_id" id="noteType" class="form-select" required>
                <option value="">Select Type</option>
                @foreach($noteTypes as $type)
                    <option value="{{ $type->id }}" {{ $note && $note->note_type_id == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <div class="form-group">
            <label for="noteContent" class="form-label">Note</label>
            <textarea id="noteContent" class="aiz-text-editor form-control" name="content" rows="5" required>{{ $note->content ?? '' }}</textarea>
        </div>
        <small id="contentAlert" class="text-danger d-none">Please enter some content.</small>
    </div>

    <button type="submit" class="btn btn-primary float-end">
        {{ $note ? 'Update' : 'Save' }}
    </button>
</form>
