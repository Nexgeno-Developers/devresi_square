@extends('frontend.layout.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $title }}</h3>
        <a class="btn btn-outline-secondary btn-sm" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
    </div>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">All</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected((string)($filters['company_id'] ?? '') === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
        </div>
    </form>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div><strong>Opening:</strong> {{ number_format($statement['opening'], 2) }}</div>
            <div><strong>Closing:</strong> {{ number_format($statement['closing'], 2) }}</div>
            <div><strong>Period:</strong> {{ $statement['from'] }} @if($statement['to'])-{{ $statement['to'] }}@endif</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Memo</th>
                            <th>Account</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Delta</th>
                            <th class="text-end">Running</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6"><strong>Opening Balance</strong></td>
                            <td class="text-end">{{ number_format($statement['opening'], 2) }}</td>
                        </tr>
                        @foreach($statement['lines'] as $line)
                            <tr>
                                <td>{{ $line['date'] }}</td>
                                <td>{{ $line['memo'] ?? '-' }}</td>
                                <td>{{ $line['account_code'] }} - {{ $line['account_name'] }}</td>
                                <td class="text-end">{{ number_format($line['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['credit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['delta'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['running'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
