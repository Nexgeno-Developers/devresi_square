@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <a href="{{ route($routeName . '.create') }}" class="btn btn-primary">Create</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Header Name</th>
                    <th>Unique Reference Number</th>
                    <th>Status</th>
                    <th>Header Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ (($records->currentPage() - 1) * $records->perPage()) + $loop->iteration }}</td>
                        <td>{{ $record->header_name }}</td>
                        <td>{{ $record->unique_reference_number }}</td>
                        <td>
                            <span class="badge bg-{{ $record->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($record->status) }}
                            </span>
                        </td>
                        <td>{{ $record->header_description ?: '-' }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route($routeName . '.edit', $record->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route($routeName . '.destroy', $record->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this invoice header?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No invoice headers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>
@endsection
