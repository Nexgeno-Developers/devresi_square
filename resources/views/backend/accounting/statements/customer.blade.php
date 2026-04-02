@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Customer</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Select customer</option>
                    @foreach($users as $id => $name)
                        <option value="{{ $id }}" @selected((string)($filters['user_id'] ?? '') === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">All</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" @selected((string)($filters['company_id'] ?? '') === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
        </div>
    </form>

    @if($statement)
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div><strong>Opening:</strong> {{ number_format($statement['opening'], 2) }}</div>
            <div><strong>Closing:</strong> {{ number_format($statement['closing'], 2) }}</div>
            <div><strong>Period:</strong> {{ $statement['from'] }} @if($statement['to'])-{{ $statement['to'] }}@endif</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Net Running Balance</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Memo</th>
                            <th>Source</th>
                            <th>Account</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Delta</th>
                            <th class="text-end">Running</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7"><strong>Opening Balance</strong></td>
                            <td class="text-end">{{ number_format($statement['opening'], 2) }}</td>
                        </tr>
                        @foreach($statement['lines'] as $line)
                            <tr>
                                <td>{{ $line['date'] }}</td>
                                <td>{{ $line['memo'] ?? '-' }}</td>
                                <td>{{ trim(($line['source_type'] ?? '') . ' ' . ($line['source_id'] ?? '')) }}</td>
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
    @endif
</div>
@endsection
