@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Estate Charges</h2>
    <a href="{{ route('admin.estate-charges.create') }}" class="btn btn-primary mb-3">Add New Charge</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">

    <table class="rs-stack-table table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Reference No</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($estateCharges as $charge)
                <tr>
                    <td data-label="#">{{ $charge->id }}</td>
                    <td class="rs-stack-title" data-label="Reference No">{{ $charge->ref_no }}</td>
                    <td data-label="Description">{{ $charge->description }}</td>
                    <td data-label="Amount">{{ $charge->amount }}</td>
                    <td data-label="Due Date">{{ $charge->due_date }}</td>
                    <td data-label="Status">{{ $charge->status }}</td>
                    <td class="rs-stack-actions">
                        <a href="{{ route('admin.estate-charges.edit', $charge) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.estate-charges.destroy', $charge) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No estate charges yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
