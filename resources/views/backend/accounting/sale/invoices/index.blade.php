@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#advanceModal">Record Advance</button>
            <a href="{{ $createUrl ?? route($routeName . '.create') }}" class="btn btn-primary">Create</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        @foreach($columns as $column)
                            @if(($column['key'] ?? '') === 'id' && ($column['label'] ?? '') === '#')
                                <td>{{ (($records->currentPage() - 1) * $records->perPage()) + $loop->parent->iteration }}</td>
                            @else
                                @php
                                    $value = data_get($record, $column['key']);
                                    $type = $column['type'] ?? 'text';
                                @endphp
                                <td>
                                    @if($type === 'boolean')
                                        <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">{{ $value ? 'Yes' : 'No' }}</span>
                                    @elseif($type === 'money')
                                        {{ number_format((float) $value, 2) }}
                                    @elseif($type === 'date')
                                        {{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '-' }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            @endif
                        @endforeach
                        <td class="d-flex gap-1">
                            <a href="{{ route($routeName . '.edit', $record->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            @if(($record->balance_amount ?? 0) > 0)
                                <button type="button"
                                        class="btn btn-sm btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#payModal-{{ $record->id }}">
                                    Pay Now
                                </button>
                            @endif
                            @php $creditAvail = optional($record->user)->available_credit ?? 0; @endphp
                            @if(($record->balance_amount ?? 0) > 0 && $creditAvail > 0)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#applyCreditModal-{{ $record->id }}">
                                    Apply Credit
                                </button>
                            @endif
                            <a href="{{ route($routeName . '.show', $record->id) }}" class="btn btn-sm btn-info">View</a>
                            <form action="{{ route($routeName . '.destroy', $record->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="text-center">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>

{{-- Record Advance Modal --}}
<div class="modal fade" id="advanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Advance (Customer Credit)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route($routeName . '.storeAdvance') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($userOptions as $uid => $uname)
                                <option value="{{ $uid }}">{{ $uname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" min="0.50" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receipt Date</label>
                        <input type="date" name="receipt_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Account</label>
                        <select name="sys_bank_account_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($bankOptions as $bid => $bname)
                                <option value="{{ $bid }}">{{ $bname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($paymentOptions as $pid => $pname)
                                <option value="{{ $pid }}">{{ $pname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($records as $record)
    @if(($record->balance_amount ?? 0) > 0)
        <div class="modal fade" id="payModal-{{ $record->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Stripe Payment - {{ $record->invoice_no }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route($routeName . '.pay', $record->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number"
                                       name="amount"
                                       class="form-control"
                                       min="0.50"
                                       step="0.01"
                                       max="{{ $record->balance_amount }}"
                                       value="{{ $record->balance_amount }}"
                                       required>
                                <small class="text-muted">Max: {{ number_format($record->balance_amount, 2) }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" class="form-control" value="gbp" maxlength="3">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Proceed to Stripe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @php $creditAvail = optional($record->user)->available_credit ?? 0; @endphp
    @if(($record->balance_amount ?? 0) > 0 && $creditAvail > 0)
        <div class="modal fade" id="applyCreditModal-{{ $record->id }}" tabindex="-1" aria-hidden="true" data-credit-modal data-balance="{{ $record->balance_amount }}">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply Credit - {{ $record->invoice_no }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route($routeName . '.applyCredit', $record->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if($record->user->creditReceipts->isEmpty())
                                <p class="text-muted mb-0">No available credits.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Credit Note #</th>
                                                <th>Date</th>
                                                <th class="text-end">Credit Amount</th>
                                                <th class="text-end">Credits Available</th>
                                                <th class="text-end">Amount to Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($record->user->creditReceipts as $idx => $credit)
                                                <tr>
                                                    <td>{{ $credit->receipt_no }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($credit->receipt_date)->format('Y-m-d') }}</td>
                                                    <td class="text-end">{{ number_format($credit->amount, 2) }}</td>
                                                    <td class="text-end">{{ number_format($credit->remaining_amount, 2) }}</td>
                                                    <td style="max-width: 160px;">
                                                        <input type="hidden" name="credits[{{ $idx }}][receipt_id]" value="{{ $credit->id }}">
                                                        <input
                                                            type="number"
                                                            name="credits[{{ $idx }}][amount]"
                                                            class="form-control form-control-sm credit-input"
                                                            min="0"
                                                            step="0.01"
                                                            max="{{ min($credit->remaining_amount, $record->balance_amount) }}"
                                                            data-available="{{ $credit->remaining_amount }}"
                                                        >
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end gap-4 mt-3">
                                    <div><strong>Amount to Credit:</strong> <span data-credit-total>0.00</span></div>
                                    <div><strong>Balance Due:</strong> <span data-credit-remaining>{{ number_format($record->balance_amount, 2) }}</span></div>
                                </div>
                                <small class="text-muted d-block mt-1">Total credit cannot exceed the invoice balance or individual credit availability.</small>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-credit-modal]').forEach(function (modal) {
        const balance = parseFloat(modal.dataset.balance || '0');
        const totalEl = modal.querySelector('[data-credit-total]');
        const remainingEl = modal.querySelector('[data-credit-remaining]');
        const inputs = modal.querySelectorAll('input.credit-input');

        const recalc = () => {
            let sum = 0;
            inputs.forEach((input) => {
                const val = parseFloat(input.value || '0') || 0;
                sum += val;
            });
            const remaining = balance - sum;
            if (totalEl) {
                totalEl.textContent = sum.toFixed(2);
                totalEl.classList.toggle('text-danger', sum > balance + 0.0001);
            }
            if (remainingEl) {
                remainingEl.textContent = remaining.toFixed(2);
                remainingEl.classList.toggle('text-danger', remaining < -0.0001);
            }
        };

        inputs.forEach((input) => input.addEventListener('input', () => {
            const maxAvail = parseFloat(input.dataset.available || '0');
            let val = parseFloat(input.value || '0') || 0;

            if (val > maxAvail) val = maxAvail;
            if (val < 0) val = 0;

            let otherSum = 0;
            inputs.forEach((el) => {
                if (el !== input) {
                    otherSum += parseFloat(el.value || '0') || 0;
                }
            });
            const remainingForThis = Math.max(0, balance - otherSum);
            if (val > remainingForThis) {
                val = remainingForThis;
            }

            input.value = val ? val.toFixed(2) : '';
            recalc();
        }));
    });
});
</script>
@endpush
