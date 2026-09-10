<div class="row g-2">
    @forelse($documents as $document)
        <div class="col-12">
            <div class="document-card card shadow-sm h-100">
                <div class="card-body d-flex flex-wrap align-items-center gap-3 py-2">
                    <span class="badge bg-secondary">{{ $document->documentType->name ?? 'N/A' }}</span>
                @php
                    // explode the CSV into an array of IDs
                    $uploadIds = $document->upload_ids
                                ? explode(',', $document->upload_ids)
                                : [];
                @endphp

                @if ($uploadIds)
                        <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
                            @foreach ($uploadIds as $uid)
                                @php
                                    $url     = uploaded_asset($uid);
                                    $ext     = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
                                @endphp

                                <div class="d-flex align-items-center gap-1">
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
                @endif
                    <span class="small text-muted">Added {{ formatDateTime($document->created_at) }}</span>
                    <span class="small text-muted">Updated {{ formatDateTime($document->updated_at) }}</span>
                    <div class="ms-auto d-flex gap-1">
                        @if($document->upload_ids)
                            <a href="{{ route('admin.documents.download', $document) }}" class="btn btn-sm btn-outline-primary">Download</a>
                        @endif
                        @if(! is_tenant_portal_user())
                            <form action="{{ route('admin.documents.share', $document) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="share_with_tenant" value="{{ $document->isSharedWithTenant() ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    {{ $document->isSharedWithTenant() ? 'Stop sharing' : 'Share with tenant' }}
                                </button>
                            </form>
                        @endif
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
    @empty
        <div class="col-12">
            @if(is_landlord_plan_user())
                <div class="lw-empty">
                    <div class="lw-empty-icon"><i class="bi bi-folder2-open"></i></div>
                    <div class="lw-empty-title">No files on this property</div>
                    <p class="mb-0">Add a tenancy agreement, inventory or other file, then share it with the tenant.</p>
                </div>
            @else
                <div class="alert alert-info">No documents found.</div>
            @endif
        </div>
    @endforelse
</div>

<div class="mt-3">
  @if(method_exists($documents, 'links'))
    {{ $documents->links() }}
  @endif
</div>
