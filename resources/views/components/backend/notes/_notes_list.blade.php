<div class="row g-3">
    @forelse($notes as $note)
        <div class="col-12">
            <div class="note-card card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary">{{ $note->noteType->name ?? 'N/A' }}</span>
                    <span class="d-flex align-items-end">
                        <small class="text-muted ms-2">Added At: {{ formatDateTime($note->created_at) }}</small>
                        <small class="text-muted ms-2">Updated At: {{ formatDateTime($note->updated_at) }}</small>
                    </span>
                </div>
                <div class="card-body">
                    {{-- <p class="card-text">{!! $note->content !!}</p> --}}
                    <p class="card-text">{!! Str::limit($note->content, 200) !!}</p>
                </div>
                <div class="card-footer text-end">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-info notes-view me-1" data-id="{{ $note->id }}"
                            title="View Full Note">
                            <i class="bi bi-eye"> View</i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger notes-edit me-1" data-id="{{ $note->id }}"
                            title="Edit Note">
                            <i class="bi bi-pencil">Edit</i>
                        </button>
                        <button type="button"
                                class="btn btn-danger btn-sm notes-delete"
                                data-id="{{ $note->id }}"
                                data-url="{{ route('admin.notes.delete', $note->id) }}"
                                data-message="Are you sure you want to delete note #{{ $note->id }}?">
                        <i class="bi bi-trash"></i> Delete
                        </button>

                        {{-- <button class="btn btn-sm btn-outline-danger notes-delete me-1" title="Delete Note"
                            data-id="{{ $note->id }}">
                            <i class="bi bi-trash">Delete</i>
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">No notes found.</div>
        </div>
    @endforelse
</div>

<div class="mt-3">
  @if(method_exists($notes, 'links'))
    {{ $notes->links() }}
  @endif
</div>