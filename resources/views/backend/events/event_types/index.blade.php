@extends('backend.layout.app')

@section('content')
<div class="container">
    <h3>Event Types 
        <a href="{{ route('backend.event_types.create') }}" class="btn btn-primary btn-sm float-end">New Type</a>
    </h3>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                {{-- <th>Slug</th> --}}
                <th>Description</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
            <tr>
                <td>{{ $loop->iteration }}</td>
                {{-- <td>{{ $type->id }}</td> --}}
                <td>{{ $type->name }}</td>
                {{-- <td>{{ $type->slug }}</td> --}}
                <td>{{ Str::limit($type->description, 50) ?? '-' }}</td>
                <td>{{ $type->created_at->format('Y-m-d') }}</td>
                <td class="text-center">
                    <a href="{{ route('backend.event_types.show', $type) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('backend.event_types.edit', $type) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('backend.event_types.destroy', $type) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Delete this event type?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
