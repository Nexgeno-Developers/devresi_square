@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('backend.accounting.reports.trial_balance') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if($report)
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div><strong>Total Debits:</strong> {{ number_format($report['total_debit'], 2) }}</div>
            <div><strong>Total Credits:</strong> {{ number_format($report['total_credit'], 2) }}</div>
            <div>
                <strong>Status:</strong>
                @if($report['is_balanced'])
                    <span class="badge bg-success">Balanced</span>
                @else
                    <span class="badge bg-danger">Imbalance: {{ number_format($report['difference'], 2) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($row['type']) }}</span></td>
                            <td class="text-end">{{ number_format($row['debit'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['credit'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="text-end">{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end">{{ number_format($report['total_credit'], 2) }}</td>
                        </tr>
                        @if(!$report['is_balanced'])
                        <tr class="table-danger">
                            <td colspan="3">Difference (Imbalance)</td>
                            <td class="text-end" colspan="2">{{ number_format($report['difference'], 2) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
