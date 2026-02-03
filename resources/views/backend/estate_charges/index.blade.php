@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Estate Charges</h2>
    <a href="{{ route('backend.estate_charges.create') }}" class="btn btn-primary mb-3">Add New Charge</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
            @foreach($estateCharges as $charge)
                <tr>
                    <td>{{ $charge->id }}</td>
                    <td>{{ $charge->ref_no }}</td>
                    <td>{{ $charge->description }}</td>
                    <td>{{ $charge->amount }}</td>
                    <td>{{ $charge->due_date }}</td>
                    <td>{{ $charge->status }}</td>
                    <td>
                        <a href="{{ route('backend.estate_charges.edit', $charge) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('backend.estate_charges.destroy', $charge) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
