<div class="row g-3">
    @forelse($documents as $document)
        <div class="col-12">
            <div class="document-card card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary">{{ $document->documentType->name ?? 'N/A' }}</span>
                    <span class="d-flex align-items-end">
                        <small class="text-muted ms-2">Added At: {{ formatDateTime($document->created_at) }}</small>
                        <small class="text-muted ms-2">Updated At: {{ formatDateTime($document->updated_at) }}</small>
                    </span>
                </div>
                @php
                    // explode the CSV into an array of IDs
                    $uploadIds = $document->upload_ids
                                ? explode(',', $document->upload_ids)
                                : [];
                @endphp

                @if ($uploadIds)
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mb-3" style="gap:10px;">
                            @foreach ($uploadIds as $uid)
                                @php
                                    $url     = uploaded_asset($uid);
                                    $ext     = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
                                @endphp

                                <div class="me-2 text-center">
                                    <a href="{{ $url }}" target="_blank" class="text-decoration-none d-block mb-1">
                                        @if($isImage)
                                            <i class="fas fa-image fa-2x"></i>
                                        @else
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        @endif
                                    </a>
                                    {!! attachmentViewer(
                                        $url,
                                        'Preview',
                                        'btn btn-outline-secondary btn-sm',
                                        'lg'
                                    ) !!}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="card-footer text-end">
                    <div class="d-flex justify-content-end">
                        {{-- <button class="btn btn-sm btn-outline-info documents-view me-1" data-id="{{ $document->id }}"
                            title="View Full document">
                            <i class="bi bi-eye"> View</i>
                        </button> --}}
                        <button class="btn btn-sm btn-outline-danger documents-edit me-1" data-id="{{ $document->id }}"
                            title="Edit document">
                            <i class="bi bi-pencil">Edit</i>
                        </button>
                        <button type="button"
                                class="btn btn-danger btn-sm documents-delete"
                                data-id="{{ $document->id }}"
                                data-url="{{ route('admin.documents.delete', $document->id) }}"
                                data-message="Are you sure you want to delete document #{{ $document->id }}?">
                        <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">No documents found.</div>
        </div>
    @endforelse
</div>

<div class="mt-3">
  @if(method_exists($documents, 'links'))
    {{ $documents->links() }}
  @endif
</div>