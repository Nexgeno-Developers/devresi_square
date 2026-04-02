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
                <a href="{{ route('backend.accounting.reports.profit_loss') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if($report)
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total Income</h6>
                    <h4 class="text-success">{{ number_format($report['total_income'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total Expenses</h6>
                    <h4 class="text-danger">{{ number_format($report['total_expense'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Net {{ $report['net_income'] >= 0 ? 'Income' : 'Loss' }}</h6>
                    <h4 class="{{ $report['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($report['net_income'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white"><strong>Income</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse($report['income_rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">No income recorded</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="2">Total Income</td><td class="text-end">{{ number_format($report['total_income'], 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-danger text-white"><strong>Expenses</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Account</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse($report['expense_rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">No expenses recorded</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="2">Total Expenses</td><td class="text-end">{{ number_format($report['total_expense'], 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <h5>Net {{ $report['net_income'] >= 0 ? 'Income' : 'Loss' }}: <span class="{{ $report['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format(abs($report['net_income']), 2) }}</span></h5>
        </div>
    </div>
    @endif
</div>
@endsection
