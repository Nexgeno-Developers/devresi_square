@extends('backend.layout.app')

@section('content')
    <div class="container">
        <h1>Repair Issues</h1>

        <!-- Display success message if any -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter and Search -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="{{ route('admin.property_repairs.index') }}">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by Property Name or ID"
                            value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.property_repairs.index') }}">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Filter by Status --</option>
                        @foreach(['Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="col-md-2 text-end">
                <a href="{{ route('admin.property_repairs.index') }}" class="btn btn-secondary">Reset Filters</a>
            </div>
        </div>

        <!-- Table displaying repair issues -->
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repairIssues as $issue)
                    <tr>
                        <td>{{ $issue->id }}</td>
                        <td>{{ getPropertyDetails($issue->property_id, ['prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country']) }}</td>
                        <td>{{ getRepairCategoryDetails($issue->repair_category_id) }}</td>
                        <td>{{ $issue->description }}</td>
                        <td><span class="badge bg-info">{{ $issue->status }}</span></td>
                        <td>{{ $issue->created_at->format('d M, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.property_repairs.show', $issue->id) }}" class="btn btn-info btn-sm">View</a>
                            <a href="{{ route('admin.property_repairs.edit', $issue->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('admin.property_repairs.delete', $issue->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No Repair Issues Found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $repairIssues->appends(request()->query())->links() }}
        </div>
    </div>
@endsection

@section('page.scripts')
    <script>
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                if (!confirm('Are you sure you want to delete this repair issue?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
