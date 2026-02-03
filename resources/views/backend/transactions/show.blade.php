@extends('backend.layout.app')

@section('content')
<div class="container">
    <h1>Transaction Details</h1>

    <table class="table table-bordered">
        <tr><th>ID</th><td>{{ $transaction->id }}</td></tr>
        <tr><th>Date</th><td>{{ $transaction->date }}</td></tr>
        <tr><th>Type</th><td>{{ ucfirst($transaction->transaction_type) }}</td></tr>
        <tr><th>Category</th><td>{{ $transaction->category->name ?? '-' }}</td></tr>
        <tr><th>Amount</th><td>{{ number_format($transaction->amount, 2) }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($transaction->status) }}</td></tr>
        <tr><th>Reference</th><td>{{ $transaction->transaction_reference ?? '-' }}</td></tr>
        <tr><th>Notes</th><td>{{ $transaction->notes ?? '-' }}</td></tr>
    </table>

    <a href="{{ route('backend.transactions.index') }}" class="btn btn-secondary">Back</a>
    <a href="{{ route('backend.transactions.edit', $transaction) }}" class="btn btn-warning">Edit</a>
</div>
@endsection
