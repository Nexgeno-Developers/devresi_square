@extends('backend.layout.app')

@section('content')
    <h1>Edit Tenancy Sub Status</h1>

    <form action="{{ route('admin.tenancy_sub_statuses.update', $tenancySubStatus->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Sub Status Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $tenancySubStatus->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update</button>
        <a href="{{ route('admin.tenancy_sub_statuses.index') }}" class="btn btn-secondary mt-3">Back to List</a>
    </form>
@endsection
