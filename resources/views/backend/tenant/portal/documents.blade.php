@extends('backend.layout.app')
@include('backend.tenant.portal._styles')

@section('content')
<div class="tenant-portal">
    <div class="tp-hero">
        <div>
            <p class="tp-kicker">Documents</p>
            <h1>Shared files</h1>
            <p>Only documents marked as shared or portal-visible for your tenancy.</p>
        </div>
    </div>

    <div class="tp-card">
        @if($documents->isEmpty())
            <p class="tp-empty">No shared documents yet. Your gas safety, EPC or tenancy agreement will appear here when they are shared with you.</p>
        @else
            <table class="tp-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Updated</th>
                        <th>Visibility</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                        <tr>
                            <td>{{ $document->documentType?->name ?: 'Document' }}</td>
                            <td>{{ $document->updated_at?->format('d M Y') ?: '—' }}</td>
                            <td><span class="tp-pill">{{ $document->visibility }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
