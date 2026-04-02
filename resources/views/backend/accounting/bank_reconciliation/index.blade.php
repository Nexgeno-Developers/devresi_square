@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Bank Account</label>
                <select name="sys_bank_account_id" class="form-select" required>
                    <option value="">Select bank account</option>
                    @foreach($bankAccounts as $id => $name)
                        <option value="{{ $id }}" @selected((string)($filters['sys_bank_account_id'] ?? '') === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Statement Date</label>
                <input type="date" name="statement_date" value="{{ $filters['statement_date'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bank Statement Balance</label>
                <input type="number" step="0.01" name="statement_balance" value="{{ $filters['statement_balance'] ?? '' }}" class="form-control" placeholder="Enter bank balance">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Compare</button>
            </div>
        </div>
    </form>

    @if($reconciliation)
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">GL Balance</h6>
                    <h4>{{ number_format($reconciliation['gl_balance'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted">Bank Statement Balance</h6>
                    <h4>{{ number_format($reconciliation['statement_balance'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center {{ abs($reconciliation['difference']) < 0.01 ? 'border-success' : 'border-danger' }}">
                <div class="card-body">
                    <h6 class="text-muted">Difference</h6>
                    <h4 class="{{ abs($reconciliation['difference']) < 0.01 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($reconciliation['difference'], 2) }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    @if(abs($reconciliation['difference']) < 0.01)
    <div class="alert alert-success">Bank account is reconciled. GL balance matches the bank statement.</div>
    @else
    <div class="alert alert-warning">
        There is a difference of <strong>{{ number_format(abs($reconciliation['difference']), 2) }}</strong> between the GL and bank statement. Review the transactions below.
    </div>
    @endif

    <form method="POST" action="{{ route('backend.accounting.bank_reconciliation.reconcile') }}">
        @csrf
        <input type="hidden" name="sys_bank_account_id" value="{{ $filters['sys_bank_account_id'] }}">
        <input type="hidden" name="statement_date" value="{{ $filters['statement_date'] ?? '' }}">
        <input type="hidden" name="statement_balance" value="{{ $filters['statement_balance'] ?? '' }}">
        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional reconciliation notes"></textarea>
        </div>
        <button type="submit" class="btn btn-success mb-3">Save Reconciliation Record</button>
    </form>

    @if($glLines->isNotEmpty())
    <div class="card">
        <div class="card-header"><strong>GL Transactions for this Bank Account</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Memo</th>
                            <th>Source</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($glLines as $line)
                        <tr>
                            <td>{{ $line->date }}</td>
                            <td>{{ $line->memo ?? '-' }}</td>
                            <td>{{ $line->source_type ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float)$line->debit, 2) }}</td>
                            <td class="text-end">{{ number_format((float)$line->credit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
