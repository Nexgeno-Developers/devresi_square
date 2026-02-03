@extends('backend.layout.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Transactions</h1>

    <a href="{{ route('backend.transactions.create') }}" class="btn btn-primary mb-3">+ Add Transaction</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Reference</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
                <tr>
                    <td>{{ $txn->id }}</td>
                    <td>{{ formatDate($txn->transaction_date) }}</td>
                    <td>{{ ucfirst($txn->transaction_type) }}</td>
                    <td>{{ $txn->category->name ?? '-' }}</td>
                    <td>{{ number_format($txn->amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $txn->status === 'completed' ? 'success' : ($txn->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($txn->status) }}
                        </span>
                    </td>
                    <td>{{ $txn->transaction_reference ?? '-' }}</td>
                    <td>
                        <a href="{{ route('backend.transactions.show', $txn) }}" class="btn btn-sm btn-info">View</a>
                        <a href="{{ route('backend.transactions.edit', $txn) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('backend.transactions.destroy', $txn) }}" method="POST" style="display:inline-block;">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this transaction?')" class="btn btn-sm btn-danger">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No transactions found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $transactions->links() }}
</div>
@endsection
