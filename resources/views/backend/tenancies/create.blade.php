@extends('backend.layout.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid pt-4 pb-5 lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>Add tenancy</h4>
            <p>Link a tenant to a property, then set rent and the deposit. You can invite them to the portal after saving.</p>
        </div>
        <a href="{{ route('admin.tenancies.all') }}" class="btn btn-light">Back</a>
    </div>
    <div class="card lw-card">
        <div class="card-body">
            @include('backend.tenancies._create-form')
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('asset/js/select2.min.js') }}"></script>
    @include('backend.tenancies._create-form-scripts')
@endpush
