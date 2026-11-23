@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Invoices</h2>

    <!-- Filters Section -->
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="mb-3">
        <div class="row justify-content-end">
            {{-- <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Invoice No, User, Property..." 
                       value="{{ request('search') }}">
            </div> --}}
            <div class="col-md-4">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partially paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Invoices Table -->
    <table class="rs-stack-table table table-bordered">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Work Order</th>
                <th>Property</th>
                <th>User</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td class="rs-stack-title" data-label="Invoice No">{{ $invoice->invoice_number }}</td>
                <td data-label="Work Order">{{ optional($invoice->workOrder)->works_order_no }}</td>
                <td data-label="Property">{{ getPropertyDetails($invoice->property_id, ['prop_ref_no', 'prop_name', 'line_1', 'line_2', 'city', 'country']) }}</td>
                <td data-label="User">{{ optional($invoice->user)->name ?? 'N/A' }}</td>
                <td data-label="Total Amount">{{ getPoundSymbol() }} {{ number_format($invoice->total_amount, 2) }}</td>
                <td data-label="Status">
                    <span class="mb-2 badge {{ getInvoiceStatusDetails($invoice->status_id)['badge'] }}">
                        {{ getInvoiceStatusDetails($invoice->status_id)['text'] }}
                    </span>
                </td>
                <td class="rs-stack-actions">
                    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-info btn-sm">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $invoices->appends(request()->query())->links() }}
    </div>
</div>
@endsection
