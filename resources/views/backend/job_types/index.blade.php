@extends('backend.layout.app')

@section('content')
<div class="container my-4">
    <h2 class="mb-4">Job Types Management</h2>

    <!-- Add New Job Type Form -->
    <form action="{{ route('admin.job_types.store') }}" method="POST" class="mb-4">
        @csrf
        <div class="mb-3">
            <label for="jobTypeName" class="form-label">Job Type Name</label>
            <input type="text" id="jobTypeName" name="name" class="form-control" placeholder="Enter Job Type" required>
        </div>

        <div class="mb-3">
            <label for="parentType" class="form-label">Parent Type</label>
            <select id="parentType" name="parent_id" class="form-select">
                <option value="">None (Top Level)</option>
                @foreach ($jobTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="orderLevel" class="form-label">Order Level</label>
            <input type="number" id="orderLevel" name="order_level" class="form-control" min="0" value="0">
        </div>

        <button type="submit" class="btn btn-primary">Add</button>
    </form>
    
    <!-- Job Types Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Job Type</th>
                <th>Parent</th>
                <th>Order Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($jobTypes as $type)
            <tr>
                <td>{{ $type->name }}</td>
                <td>{{ $type->parent ? $type->parent->name : 'None' }}</td>
                <td>{{ $type->order_level }}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $type->id }})">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this job type?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript for confirming the delete action -->
<script>
    function confirmDelete(jobTypeId) {
        // Set the form action dynamically using the Laravel route helper
        var form = document.getElementById('deleteForm');       

        // Set the form action to the correct route using Laravel's route() helper
        form.action = "{{ route('admin.job_types.delete', ':id') }}".replace(':id', jobTypeId);

        // Show the modal
        var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        myModal.show();
    }
</script>

@endsection
