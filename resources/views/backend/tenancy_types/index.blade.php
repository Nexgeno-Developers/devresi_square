@extends('backend.layout.app')

@section('content')
    <h1>All Tenancy Types</h1>

    <a href="{{ route('admin.tenancy_types.create') }}" class="btn btn-primary mb-3">Create New Tenancy Type</a>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenancyTypes as $tenancyType)
                <tr>
                    <td>{{ $tenancyType->name }}</td>
                    <td>
                        <a href="{{ route('admin.tenancy_types.edit', $tenancyType->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('admin.tenancy_types.destroy', $tenancyType->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
