@extends('backend.layout.app')

@section('content')
    <h1>All Tenancy Sub Statuses</h1>

    <a href="{{ route('admin.tenancy_sub_statuses.create') }}" class="btn btn-primary mb-3">Create New Sub Status</a>

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
            @foreach($tenancySubStatuses as $tenancySubStatus)
                <tr>
                    <td>{{ $tenancySubStatus->name }}</td>
                    <td>
                        <a href="{{ route('admin.tenancy_sub_statuses.edit', $tenancySubStatus->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('admin.tenancy_sub_statuses.destroy', $tenancySubStatus->id) }}" method="POST" style="display:inline;">
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
