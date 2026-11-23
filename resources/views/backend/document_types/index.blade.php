@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
<div>
    <h2 class="mb-4">Document Types</h2>

    <a href="{{ route('admin.document-types.create') }}" class="btn btn-primary mb-3">
        <i class="fas fa-plus-circle"></i> Add New
    </a>

    <div class="table-responsive">

    <table class="rs-stack-table table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Created At</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documentTypes as $index => $type)
                <tr>
                    <td data-label="#">{{ $index + 1 }}</td>
                    <td class="rs-stack-title" data-label="Name">{{ $type->name }}</td>
                    <td data-label="Created At">{{ formatDateTime($type->created_at) }}</td>
                    <td class="rs-stack-actions text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('admin.document-types.edit', $type) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                    
                            <form action="{{ route('admin.document-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                    
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No document types found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        {{ $documentTypes->links() }}
    </div>
</div>
</div>
@endsection
