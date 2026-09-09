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
                <th>Property</th>
                <th>Status</th>
                <th>Purchased</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ownerGroups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td>{{ $group->property?->full_address ?: ($group->property?->line_1 ?: 'Property #'.$group->property_id) }}</td>
                    <td>{{ ucfirst($group->status ?: '') }}</td>
                    <td>{{ $group->purchased_date }}</td>
                    <td>
                        <a href="{{ route('admin.owner-groups.edit', $group) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.owner-groups.delete_group', $group) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No owner groups yet. Add a property, then create an owner group.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
