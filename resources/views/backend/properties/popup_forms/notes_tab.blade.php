@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode with Bootstrap 5 Cards -->
    <!-- Add New Note Card -->
    <div class="mb-3">
        <button class="btn btn-outline-primary addForm" data-form="notes_tab" data-id="{{ $property->id }}">
            Add New Note
        </button>
    </div>
    <div class="row g-3 mb-3 note-list">
        @forelse($notes as $note)
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary">{{ $note->noteType->name ?? 'N/A' }}</span>
                        <span class="d-flex align-items-end">
                            <small class="text-muted ms-2">Added At: {{ $note->created_at->format('M d, Y') }}</small>
                            <small class="text-muted ms-2">Updated At: {{ $note->updated_at->format('M d, Y') }}</small>
                        </span>
                        {{-- <button class="btn btn-outline-danger btn-sm editForm" data-form="notes_tab"
                            data-id="{{ $property->id }}" data-note-id="{{ $note->id }}" title="Edit Note">
                            <i class="bi bi-pencil"></i>
                        </button> --}}
                    </div>
                    <div class="card-body">
                        {{-- <p class="card-text">{!! $note->content !!}</p> --}}
                        <p class="card-text">{!! Str::limit(   $note->content, 200) !!}</p>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex justify-content-end">
                            {{-- <button class="btn btn-sm btn-outline-info viewNote me-1" data-type="{{ $note->type }}"
                                data-content="{{ htmlentities($note->content) }}" title="View Full Note">
                                <i class="bi bi-eye"> View</i>
                            </button> --}}
                            <button class="btn btn-sm btn-outline-info viewNote me-1" data-type="{{ $note->noteType->name ?? 'N/A' }}"
                                data-id="{{ $note->id }}" data-url="{{ route('admin.notes.show', $note->id) }}" title="View Full Note">
                                <i class="bi bi-eye"> View</i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger editNote editForm me-1" data-form="notes_tab"
                                data-id="{{ $property->id }}" data-note-id="{{ $note->id }}" title="Edit Note">
                                <i class="bi bi-pencil">Edit</i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger deleteNote me-1" title="Delete Note"
                            data-note-id="{{ $note->id }}" onclick="confirmModal('{{ url(route('admin.notes.delete', $note->id)) }}', responseHandler)">
                            <i class="bi bi-trash">Delete</i>
                        </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    No notes available.
                </div>
            </div>
        @endforelse
    </div>

@else
    <form id="propertyNotesTabForm">
        @csrf
        <input type="hidden" name="property_id" value="{{ $property->id }}">
        <input type="hidden" name="form_type" value="notes_tab">
        <input type="hidden" name="note_id" value="{{ $note->id ?? '' }}">
        
        <div class="mb-3">
            <label for="noteType" class="form-label">Type</label>
            <select name="note_type_id" id="noteType" class="form-select">
                @foreach($noteTypes as $type)
                    <option value="{{ $type->id }}" {{ isset($note) && $note->note_type_id == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="notes_tab" class="form-label">Note</label>
            <textarea class="aiz-text-editor" name="content">{{ $note->content ?? '' }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary float-end">
            {{ isset($note) ? 'Update' : 'Save' }}
        </button>
    </form>
@endif