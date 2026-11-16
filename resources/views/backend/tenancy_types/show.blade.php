@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h1>Tenancy Type Details</h1>

    <p><strong>Name:</strong> {{ $tenancyType->name }}</p>

    <a href="{{ route('admin.tenancy_types.index') }}" class="btn btn-primary">Back to List</a>
</div>
@endsection
