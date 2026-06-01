@extends('backend.layout.app')

@section('content')
    <h1>Edit Designation</h1>

    <form action="{{ route('admin.designations.update', $designation->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ $designation->title }}" required>
        </div>
        <button type="submit" class="btn btn-warning mt-3">Update Designation</button>
    </form>
@endsection
