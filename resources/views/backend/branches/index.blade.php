@extends('backend.layout.app')

@section('content')
<h1>Branches</h1>
<a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add Branch</a>
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($branches as $branch)
            <tr>
                <td>{{ $branch->name }}</td>
                <td>
                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-warning">Edit</a>
                    <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
