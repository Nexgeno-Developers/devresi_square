@extends('backend.layout.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Event SubTypes</h3>
            <a href="{{ route('backend.event_sub_types.create') }}" class="btn btn-primary">
                + New SubType
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($subtypes->isEmpty())
            <div class="alert alert-info">No event subtypes found.</div>
        @else
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>SubType Name</th>
                        {{-- <th>Slug</th> --}}
                        <th>Description</th>
                        <th>Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subtypes as $sub)
                        <tr>
                            <td>{{ $sub->id }}</td>
                            <td>{{ $sub->type->name }}</td>
                            <td>{{ $sub->name }}</td>
                            {{-- <td>{{ $sub->slug }}</td> --}}
                            <td>{{ Str::limit($sub->description, 50) ?? '-' }}</td>
                            <td>{{ $sub->created_at->format('Y-m-d') }}</td>
                            <td class="text-center">
                                <a href="{{ route('backend.event_sub_types.show', $sub) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('backend.event_sub_types.edit', $sub) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('backend.event_sub_types.destroy', $sub) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this subtype?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection