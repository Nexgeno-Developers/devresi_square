@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit SaaS Addon</h2>
        <a href="{{ route('backend.saas.addons.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @include('backend.saas.addons._form')
</div>
@endsection
