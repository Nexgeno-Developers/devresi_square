@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Documents</h4>
        <p>Files stored on this account. Add them from a property, then share with the tenant from here.</p>
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
            <table class="table lw-table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
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
                            <td>{{ rs_document_title($document->displayName(), $document->documentType?->name) }}</td>
                            <td>{{ $document->documentType?->name ?? '—' }}</td>
                            <td>
                                @if($document->documentable instanceof \App\Models\Property)
                                    {{ rs_property_title($document->documentable) }}
                                @elseif($document->documentable instanceof \App\Models\User)
                                    {{ rs_person($document->documentable) }}
                                @else
                                    Related record
                                @endif
                            </td>
                            <td>{{ $document->isSharedWithTenant() ? 'Shared' : 'Private' }}</td>
                            <td>{{ rs_date($document->created_at) }}</td>
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
                            <td colspan="6">
                                <div class="lw-empty">
                                    <div class="lw-empty-icon"><i class="bi bi-folder2-open"></i></div>
                                    <div class="lw-empty-title">No documents yet</div>
                                    <p class="mb-3">Open a property and use Add document, then share it with the tenant here.</p>
                                    <a href="{{ route('admin.properties.index') }}" class="btn lw-btn-primary">Open properties</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
