@php
    $preset = $filters['preset'] ?? 'this_month';
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $warnings = $statement['warnings'] ?? [];
@endphp

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <form method="GET" class="row g-2 flex-grow-1">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="tabname" value="Statement">
            <div class="col-md-2 col-6">
                <label class="form-label">Preset</label>
                <select name="preset" class="form-select" onchange="this.form.submit()">
                    <option value="this_month" @selected($preset==='this_month')>This Month</option>
                    <option value="last_month" @selected($preset==='last_month')>Last Month</option>
                    <option value="ytd" @selected($preset==='ytd')>Year to Date</option>
                    <option value="custom" @selected($preset==='custom')>Custom</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2 col-6 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
            <div class="col-md-2 col-12 d-flex align-items-end">
                <a class="btn btn-outline-secondary w-100"
                   href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
            </div>
        </form>
    </div>
</div>

@if(!empty($warnings))
    <div class="alert alert-warning">
        @foreach($warnings as $warn)
            <div>{{ $warn }}</div>
        @endforeach
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Beginning Balance</div>
                <div class="h5 mb-0">{{ number_format($statement['opening'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Invoiced Amount</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['invoiced'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Amount Paid</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['paid'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance Due</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['balance_due'] ?? $statement['closing'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Statement for {{ $user->name }}</h5>
            <div class="text-muted small">{{ $statement['from'] }} @if($statement['to']) - {{ $statement['to'] }} @endif</div>
        </div>

        @if(($statement['lines'] ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">No transactions found for the selected period.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Details</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Delta</th>
                            <th class="text-end">Running</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5"><strong>Opening Balance</strong></td>
                            <td class="text-end">{{ number_format($statement['opening'], 2) }}</td>
                        </tr>
                        @foreach($statement['lines'] as $line)
                            <tr>
                                <td>{{ $line['date'] }}</td>
                                <td>
                                    {{ $line['memo'] ?? '-' }}
                                    <div class="text-muted small">
                                        {{ $line['account_code'] ?? '' }} {{ $line['account_name'] ?? '' }}
                                        @if(!empty($line['source_type']))
                                            ({{ $line['source_type'] }} {{ $line['source_id'] ?? '' }})
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($line['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['credit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['delta'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['running'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
