@extends('backend.layout.app')

@section('content')


@php
    // Table headers
    $headers = ['id' => 'id', 'name' => 'Name', 'status' => 'Status', 'created_at' => 'Created Date', 'Actions' => ''];

@endphp

<div class="container-fluid">
    <h1>Categories</h1>
    <div class="top_button">
        <a href="{{ route('user-categories.create') }}" class="btn btn_secondary">Create Category</a>
    </div>

        <table class="table mt-3 | rs_table ">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->status ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <a href="{{ route('user-categories.edit', $category->id) }}" class="btn btn-sm btn_outline_primary me-2">Edit</a>
                            <form action="{{ route('user-categories.destroy', $category->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

</div>
@endsection
