@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <h2>Account Headers List</h2>
    <a href="{{ route('backend.account_headers.create') }}" class="btn btn-primary mb-3">Create Account Header</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Charge On</th>
                <th>Who Can View</th>
                <th>Reminders</th>
                <th>Agent Fees</th>
                <th>Bank Details Required</th>
                <th>Charge In</th>
                <th>Duration</th>
                <th>Settle Through</th>
                <th>Penalty Type</th>
                <th>Tax Included</th>
                <th>Tax Type</th>
                <th>Transaction Between</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accountHeaders as $header)
            <tr>
                <td>{{ $header->name }}</td>
                <td>{{ ucfirst($header->charge_on) }}</td>
                <td>{{ ucfirst($header->who_can_view) }}</td>
                <td>{!! booleanBadge($header->reminders) !!}</td>
                <td>{!! booleanBadge($header->agent_fees) !!}</td>
                <td>{!! booleanBadge($header->require_bank_details) !!}</td>
                <td>{{ ucfirst($header->charge_in) }}</td>
                <td>{!! booleanBadge($header->can_have_duration) !!}</td>
                <td>{{ ucwords(str_replace('_', ' ', $header->settle_through)) }}</td>
                <td>{{ $header->penalty_type ? ucfirst($header->penalty_type) : '-' }}</td>
                <td>{!! booleanBadge($header->tax_included) !!}</td>
                <td>{{ $header->tax_type ? ucfirst($header->tax_type) : '-' }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $header->transaction_between)) }}</td>
                <td>
                    <a href="{{ route('backend.account_headers.edit', $header->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('backend.account_headers.destroy', $header->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this header?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="14" class="text-center">No Account Headers Found.</td>
            </tr>
            @endforelse
        </tbody>

    </table>

    {{ $accountHeaders->links() }}
</div>
@endsection
