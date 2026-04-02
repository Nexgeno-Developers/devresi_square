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
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('backend.accounting.reports.balance_sheet') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    @if($report)
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div><strong>As of:</strong> {{ $report['as_of'] }}</div>
            <div>
                <strong>Equation:</strong>
                @if($report['is_balanced'])
                    <span class="badge bg-success">Assets = Liabilities + Equity (Balanced)</span>
                @else
                    <span class="badge bg-danger">Imbalance: {{ number_format($report['total_assets'] - $report['total_liabilities_and_equity'], 2) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><strong>Assets</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Account</th><th class="text-end">Balance</th></tr>
                        </thead>
                        <tbody>
                            @forelse($report['asset_rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">No assets</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="2">Total Assets</td><td class="text-end">{{ number_format($report['total_assets'], 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><strong>Liabilities</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Account</th><th class="text-end">Balance</th></tr>
                        </thead>
                        <tbody>
                            @forelse($report['liability_rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">No liabilities</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="2">Total Liabilities</td><td class="text-end">{{ number_format($report['total_liabilities'], 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Equity</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Account</th><th class="text-end">Balance</th></tr>
                        </thead>
                        <tbody>
                            @forelse($report['equity_rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                            @empty
                            @endforelse
                            <tr class="table-info">
                                <td></td>
                                <td>Retained Earnings (Net Income)</td>
                                <td class="text-end">{{ number_format($report['retained_earnings'], 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr><td colspan="2">Total Equity</td><td class="text-end">{{ number_format($report['total_equity'], 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body fw-bold d-flex justify-content-between">
                    <span>Total Liabilities & Equity</span>
                    <span>{{ number_format($report['total_liabilities_and_equity'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
