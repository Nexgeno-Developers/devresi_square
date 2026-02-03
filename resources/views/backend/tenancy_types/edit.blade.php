@extends('backend.layout.app')

@section('content')
    <h1>Edit Tenancy Type</h1>

    <form action="{{ route('admin.tenancy_types.update', $tenancyType->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Tenancy Type Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $tenancyType->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update</button>
        <a href="{{ route('admin.tenancy_types.index') }}" class="btn btn-secondary mt-3">Back to List</a>
    </form>
@endsection
