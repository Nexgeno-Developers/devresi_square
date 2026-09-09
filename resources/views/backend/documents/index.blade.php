@extends('backend.layout.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Documents</h4>
            <div class="text-muted">Files stored on this account. You can also add documents from a property page.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Attached to</th>
                        <th>Tenant</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td>{{ $document->documentType?->name ?? 'Untitled' }}</td>
                            <td>
                                @if($document->documentable instanceof \App\Models\Property)
                                    {{ $document->documentable->full_address ?: ($document->documentable->line_1 ?: 'Property #'.$document->documentable_id) }}
                                @elseif($document->documentable instanceof \App\Models\User)
                                    {{ $document->documentable->name ?: $document->documentable->email }}
                                @else
                                    {{ class_basename((string) $document->documentable_type) }} #{{ $document->documentable_id }}
                                @endif
                            </td>
                            <td>{{ $document->isSharedWithTenant() ? 'Shared' : 'Private' }}</td>
                            <td>{{ $document->created_at ? formatDateTime($document->created_at) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if($document->upload_ids)
                                    <a href="{{ route('admin.documents.download', $document) }}" class="btn btn-sm btn-outline-primary">Download</a>
                                @endif
                                <form action="{{ route('admin.documents.share', $document) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="share_with_tenant" value="{{ $document->isSharedWithTenant() ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $document->isSharedWithTenant() ? 'Stop sharing' : 'Share with tenant' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No documents on this account yet. Add files from a property page, then share them with the tenant here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
