@extends('backend.layout.app')

@section('content')
<div class="container-fluid pt-4 pb-5 lw-page" data-tenancy-show="1">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>Tenancy</h4>
            <p>
                {{ $tenancy->property?->short_title ?? ($tenancy->property?->line_1 ?? 'Property') }}
                @if($tenancy->property?->short_address)
                    <span class="text-muted">· {{ $tenancy->property->short_address }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="btn lw-btn-primary">Edit tenancy</a>
            <a href="{{ route('admin.tenancies.all') }}" class="btn lw-btn-secondary">All tenancies</a>
        </div>
    </div>
    <div class="card lw-card">
        <div class="card-body">
            @include('backend.tenancies.show')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('asset/backend/js/common-documents.js') }}"></script>
@endpush
