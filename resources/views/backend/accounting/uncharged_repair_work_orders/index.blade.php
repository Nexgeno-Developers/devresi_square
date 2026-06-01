@extends('backend.layout.app')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Uncharged Repair Work Order</h4>
            <form method="GET" action="{{ route('backend.accounting.uncharged_repair_work_orders.index') }}" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search work orders..."
                    value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('backend.accounting.uncharged_repair_work_orders.index') }}" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Work Order No</th>
                        <th>Repair Ref</th>
                        <th>Property</th>
                        <th>Final Contractor</th>
                        <th>Charge To</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $workOrder)
                        @php
                            $total = $workOrder->items->sum('total_price');
                            $repairIssue = $workOrder->repairIssue;
                        @endphp
                        <tr>
                            <td>{{ $workOrder->works_order_no }}</td>
                            <td>{{ $repairIssue->reference_number ?? '-' }}</td>
                            <td>{{ $repairIssue ? getPropertyDetails($repairIssue->property_id, ['prop_name', 'line_1', 'city', 'postcode']) : '-' }}</td>
                            <td>{{ $repairIssue->finalContractor->name ?? '-' }}</td>
                            <td>{{ $workOrder->invoice_to ?? '-' }}</td>
                            <td>{{ $workOrder->status ?? '-' }}</td>
                            <td>
                                {{ $workOrder->tentative_start_date ? formatDate($workOrder->tentative_start_date) : '-' }}
                                -
                                {{ $workOrder->tentative_end_date ? formatDate($workOrder->tentative_end_date) : '-' }}
                            </td>
                            <td class="text-end">{{ getPoundSymbol() }}{{ number_format($total, 2) }}</td>
                            <td class="text-end">
                                @if($repairIssue)
                                    <a href="{{ route('admin.property_repairs.index_tabbed', ['repair_id' => $repairIssue->id, 'tabname' => 'work-order']) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Open
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No uncharged repair work orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $workOrders->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
