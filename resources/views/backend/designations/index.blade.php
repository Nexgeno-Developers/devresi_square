@extends('backend.layout.app')

@section('content')
    <h1>Designations</h1>
    <div class="top_button">
        <a href="{{ route('admin.designations.create') }}" class="btn btn_secondary btn-sm">Add New Designation</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table mt-3">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($designations as $designation)
                <tr>
                    <td>{{ $designation->id }}</td>
                    <td>{{ $designation->title }}</td>
                    <td>
                        <a href="{{ route('admin.designations.edit', $designation->id) }}" class="btn btn_outline_primary btn-sm me-2">Edit</a>
                        <form action="{{ route('admin.designations.destroy', $designation->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
