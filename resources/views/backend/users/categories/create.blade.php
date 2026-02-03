@extends('backend.layout.app')

@section('content')
<div class="container-fluid">
    <h1>Create Category</h1>
    <div class="row">
        <div class="col-md-4 col-12">

            <form action="{{ route('user-categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="top_button">
                    <button type="submit" class="btn btn_secondary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
