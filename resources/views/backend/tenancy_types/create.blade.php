@extends('backend.layout.app')

@section('content')
    <h1>Create Tenancy Type</h1>

    <form action="{{ route('admin.tenancy_types.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Tenancy Type Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.tenancy_types.index') }}" class="btn btn-secondary mt-3">Back to List</a>
    </form>
@endsection
