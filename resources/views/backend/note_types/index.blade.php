@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h2>Note Types</h2>

    <a href="{{ route('admin.note-types.create') }}" class="btn btn-primary mb-3">Add New</a>

    <div class="table-responsive">

    <table class="rs-stack-table table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($noteTypes as $index => $type)
                <tr>
                    <td data-label="#">{{ $index + 1 }}</td>
                    <td class="rs-stack-title" data-label="Name">{{ $type->name }}</td>
                    <td data-label="Created At">{{ formatDateTime($type->created_at) }}</td>
                    <td class="rs-stack-actions">
                        <a href="{{ route('admin.note-types.edit', $type) }}" class="btn btn-sm btn-warning">Edit</a>

                        <form action="{{ route('admin.note-types.destroy', $type) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No note types found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="d-flex justify-content-end mt-3">
    {{ $noteTypes->links() }}
</div>
</div>
@endsection