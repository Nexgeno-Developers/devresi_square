@extends('backend.layout.app')

@section('content')
<div class="container-fluid">
    <h1>Edit Category</h1>
    <form action="{{ route('user-categories.update', $userCategory->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Category Name</label>
            <input type="text" class="form-control" name="name" value="{{ $userCategory->name }}" required>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" class="form-control" required>
                <option value="1" {{ $userCategory->status ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !$userCategory->status ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
