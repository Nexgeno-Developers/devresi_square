@extends('backend.layout.app')

@section('content')
    <h1>Tenancy Sub Status Details</h1>

    <p><strong>Name:</strong> {{ $tenancySubStatus->name }}</p>

    <a href="{{ route('admin.tenancy_sub_statuses.index') }}" class="btn btn-primary">Back to List</a>
@endsection
