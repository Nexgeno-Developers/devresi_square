@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Owner Groups</h2>
    <a href="{{ route('admin.owner-groups.create') }}" class="btn btn-primary mb-3">Add New Owner Group</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ownerGroups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td>{{ $group->name }}</td>
                    <td>{{ $group->description }}</td>
                    <td>
                        <a href="{{ route('backend.owner_groups.edit', $group) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('backend.owner_groups.destroy', $group) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
