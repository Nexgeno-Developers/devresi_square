@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h2>Account Headers List</h2>
    <a href="{{ route('backend.account_headers.create') }}" class="btn btn-primary mb-3">Create Account Header</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">

    <table class="rs-stack-table table table-bordered table-striped">
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
                <td class="rs-stack-title" data-label="Name">{{ $header->name }}</td>
                <td data-label="Charge On">{{ ucfirst($header->charge_on) }}</td>
                <td data-label="Who Can View">{{ ucfirst($header->who_can_view) }}</td>
                <td data-label="Reminders">{!! booleanBadge($header->reminders) !!}</td>
                <td data-label="Agent Fees">{!! booleanBadge($header->agent_fees) !!}</td>
                <td data-label="Bank Details Required">{!! booleanBadge($header->require_bank_details) !!}</td>
                <td data-label="Charge In">{{ ucfirst($header->charge_in) }}</td>
                <td data-label="Duration">{!! booleanBadge($header->can_have_duration) !!}</td>
                <td data-label="Settle Through">{{ ucwords(str_replace('_', ' ', $header->settle_through)) }}</td>
                <td data-label="Penalty Type">{{ $header->penalty_type ? ucfirst($header->penalty_type) : '-' }}</td>
                <td data-label="Tax Included">{!! booleanBadge($header->tax_included) !!}</td>
                <td data-label="Tax Type">{{ $header->tax_type ? ucfirst($header->tax_type) : '-' }}</td>
                <td data-label="Transaction Between">{{ ucwords(str_replace('_', ' ', $header->transaction_between)) }}</td>
                <td class="rs-stack-actions">
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
    </div>

    {{ $accountHeaders->links() }}
</div>
@endsection
