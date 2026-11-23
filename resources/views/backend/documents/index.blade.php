@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h4>Documents</h4>
                <p>Property and tenancy documents, organised in one place.</p>
            </div>
            <a href="{{ route('admin.properties.index') }}" class="btn lw-btn-primary btn-sm">Upload Document</a>
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            @if($documents->isEmpty())
                <div class="lw-empty">
                                    <div class="lw-empty-icon"><i class="bi bi-folder2-open"></i></div>
                                    <div class="lw-empty-title">No Documents Yet</div>
                                    <p class="mb-3">Upload a document and connect it to the relevant property, tenancy or record.</p>
                                    <a href="{{ route('admin.properties.index') }}" class="btn lw-btn-primary">Upload Document</a>
                                </div>
            @else
            <div class="table-responsive">
            <table class="rs-stack-table table lw-table align-middle">
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
                    @foreach($documents as $document)
                        <tr>
                            <td class="rs-stack-title" data-label="Name">{{ rs_document_title($document->displayName(), $document->documentType?->name) }}</td>
                            <td data-label="Type">{{ $document->documentType?->name ?? '—' }}</td>
                            <td data-label="Attached to">
                                @if($document->documentable instanceof \App\Models\Property)
                                    {{ rs_property_title($document->documentable) }}
                                @elseif($document->documentable instanceof \App\Models\User)
                                    {{ rs_person($document->documentable) }}
                                @else
                                    Related record
                                @endif
                            </td>
                            <td data-label="Tenant">{{ $document->isSharedWithTenant() ? 'Shared' : 'Private' }}</td>
                            <td data-label="Added">{{ rs_date($document->created_at) }}</td>
                            <td class="rs-stack-actions text-end text-nowrap">
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
                    @endforeach
                </tbody>
            </table>
            </div>
            @endif

            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
