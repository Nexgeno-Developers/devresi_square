@extends('backend.layout.app')

@include('backend.partials.assets.select2')

@section('content')
<div class="container-fluid pt-4 pb-5 lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h4>Edit owner group</h4>
            <p>Update the ownership group for this property.</p>
        </div>
        @if($returnUrl)
            <a href="{{ $returnUrl }}" class="btn btn-light">Back to property</a>
        @else
            <a href="{{ route('admin.owner-groups.index') }}" class="btn btn-light">Back</a>
        @endif
    </div>

    <div class="card lw-card">
        <div class="card-body">
            @include('backend.owner_groups.edit-group')
        </div>
    </div>
</div>
@endsection
