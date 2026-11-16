@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Estate Charges</h2>
    <a href="{{ route('admin.estate-charges.create') }}" class="btn btn-primary mb-3">Add New Charge</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">

    <table class="table table-bordered">
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
                    <td>{{ $charge->id }}</td>
                    <td>{{ $charge->ref_no }}</td>
                    <td>{{ $charge->description }}</td>
                    <td>{{ $charge->amount }}</td>
                    <td>{{ $charge->due_date }}</td>
                    <td>{{ $charge->status }}</td>
                    <td>
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
