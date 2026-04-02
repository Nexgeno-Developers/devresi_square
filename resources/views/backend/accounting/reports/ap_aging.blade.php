@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
    </div>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">As of Date</label>
                <input type="date" name="as_of" value="{{ $filters['as_of'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
        </div>
    </form>

    @if($report)
    <div class="row mb-3">
        @php
            $bucketLabels = ['current' => '0-30 Days', '31_60' => '31-60 Days', '61_90' => '61-90 Days', '91_120' => '91-120 Days', 'over_120' => '120+ Days'];
            $bucketColors = ['current' => 'success', '31_60' => 'info', '61_90' => 'warning', '91_120' => 'danger', 'over_120' => 'dark'];
        @endphp
        @foreach($report['buckets'] as $key => $amount)
        <div class="col">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">{{ $bucketLabels[$key] }}</h6>
                    <h5 class="text-{{ $bucketColors[$key] }}">{{ number_format($amount, 2) }}</h5>
                </div>
            </div>
        </div>
        @endforeach
        <div class="col">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Total Outstanding</h6>
                    <h5 class="text-primary fw-bold">{{ number_format($report['total'], 2) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Vendor</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Days Overdue</th>
                            <th>Bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['invoice_no'] }}</td>
                            <td>{{ $row['vendor'] }}</td>
                            <td>{{ $row['invoice_date'] }}</td>
                            <td>{{ $row['due_date'] }}</td>
                            <td class="text-end">{{ number_format($row['total'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                            <td class="text-end">{{ $row['days_overdue'] }}</td>
                            <td><span class="badge bg-{{ $bucketColors[$row['bucket']] }}">{{ $bucketLabels[$row['bucket']] }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">No outstanding payables</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
