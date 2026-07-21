@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Create SaaS Addon</h2>
        <a href="{{ route('backend.saas.addons.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @include('backend.saas.addons._form')
</div>
@endsection
