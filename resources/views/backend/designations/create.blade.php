@extends('backend.layout.app')

@section('content')
    <h1>Add New Designation</h1>

    <form action="{{ route('admin.designations.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <button type="submit" class="btn btn_secondary mt-3">Create Designation</button>
    </form>
@endsection
