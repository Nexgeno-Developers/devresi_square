@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h1>Tenancy Sub Status Details</h1>

    <p><strong>Name:</strong> {{ $tenancySubStatus->name }}</p>

    <a href="{{ route('admin.tenancy_sub_statuses.index') }}" class="btn btn-primary">Back to List</a>
</div>
@endsection
