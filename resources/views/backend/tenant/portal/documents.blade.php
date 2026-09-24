@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal" data-tenant-documents="1">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Documents</p>
            <h1>Shared files</h1>
            <p>Only documents marked as shared or portal-visible for your tenancy.</p>
        </div>
    </div>

    <div class="tp-stack">
        @forelse($documents as $document)
            <article class="tp-card tp-doc-card">
                <div class="tp-doc-card-body">
                    <p class="tp-doc-title mb-1">{{ $document->displayName() }}</p>
                    <p class="tp-muted mb-0">
                        {{ $document->documentType?->name ?: 'Document' }}
                        · Updated {{ $document->updated_at?->format('d M Y') ?: '—' }}
                    </p>
                    <span class="tp-pill mt-2 d-inline-block">{{ $document->isSharedWithTenant() ? 'Shared with you' : ($document->visibility ?: 'Shared') }}</span>
                </div>
                @if($document->upload_ids)
                    <a class="tp-btn" href="{{ route('tenant.documents.download', $document) }}">Download</a>
                @endif
            </article>
        @empty
            <div class="tp-card">
                <p class="tp-empty mb-0" data-empty-docs="1">Your landlord has not shared a document yet.</p>
            </div>
        @endforelse
        @if(method_exists($documents, 'links'))
            <div class="mt-3">{{ $documents->links() }}</div>
        @endif
    </div>
</div>
@endsection
