@extends('backend.layout.app')

@php
    $status = strtoupper($invoice->status ?? 'draft');
    $linkToLabel = match ($invoice->link_to_type) {
        'Property' => $invoice->linkTo?->prop_name ?: $invoice->linkTo?->line_1 ?: 'Property #' . $invoice->link_to_id,
        'Tenancy' => 'Tenancy #' . ($invoice->link_to_id ?? '-'),
        'Contractor' => $invoice->linkTo?->name ?: 'Contractor #' . $invoice->link_to_id,
        default => '-',
    };
    $chargeToLabel = match ($invoice->charge_to_type) {
        'Owner', 'Tenant', 'Contractor' => $invoice->chargeTo?->name ?: ($invoice->charge_to_type ? $invoice->charge_to_type . ' #' . $invoice->charge_to_id : '-'),
        default => '-',
    };
    $bankAccountLabel = $invoice->bankAccount
        ? trim(($invoice->bankAccount->account_name ?: $invoice->bankAccount->bank_name ?: 'Bank Account') . ($invoice->bankAccount->account_no ? ' (' . substr($invoice->bankAccount->account_no, -4) . ')' : ''))
        : '-';
@endphp

@push('styles')
<style>
    .invoice-shell { max-width: 900px; margin: 20px auto; background: #fff; border: 1px solid #e6e9ed; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
    .invoice-hero { background: #eceff4; padding: 28px; }
    .invoice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: center; }
    .invoice-meta { text-align: right; }
    .billto { margin-top: 16px; font-size: 13px; }
    .billto strong { display:block; margin-bottom:4px; }
    .table-items { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .table-items th, .table-items td { border: 1px solid #dfe3e8; padding: 8px 10px; }
    .table-items th { background: #111; color: #fff; font-weight: 600; font-size: 12px; }
    .totals { width: 320px; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
    .totals th, .totals td { padding: 6px 10px; border: 1px solid #dfe3e8; }
    .totals th { background: #f7f8fa; text-align: left; }
    .mt-12 { margin-top: 12px; }
    .text-right { text-align: right; }
    .badge-status { font-weight: 700; font-size: 12px; text-transform: uppercase; }
    .status-PAID { color: #0f9d58; }
    .status-PARTIAL { color: #ff8c00; }
    .status-DRAFT { color: #3b82f6; }
    .status-CANCELLED { color: #6b7280; text-decoration: line-through; }
    .btn-stripe { padding: 10px 16px; border: none; background: #635bff; color: #fff; border-radius: 4px; cursor: pointer; }
    .btn-pdf { display: inline-block; padding: 8px 16px; border: none; background: #111; color: #fff; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; }
    .btn-pdf:hover { background: #333; color: #fff; }
    .notes-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 12px; }
</style>
@endpush

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    @if(($customer->available_credit ?? 0) > 0)
        <div class="alert alert-info d-flex justify-content-between align-items-center" style="max-width:900px;margin:0 auto 12px auto;">
            <span>Customer available credit: {{ number_format($customer->available_credit ?? 0, 2) }}</span>
            @if(($balance ?? 0) > 0)
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#applyCreditModal">Apply Credit</button>
            @endif
        </div>
    @endif

    <div class="invoice-shell">
        <div class="invoice-hero">
            <div class="invoice-grid">
                <div>
                    <img loading="lazy" src="https://laravel.resisquare.co.uk/asset/images/resisquare-logo.svg" height="44" alt="Resisquare">
                    <div style="margin-top:14px; font-style: italic; color:#444;">
                        {{ get_setting('company_name') ?: 'Resisquare' }}<br>
                        {{ get_setting('contact_address') ?: 'UK, London' }}<br>
                        {{ get_setting('contact_email') ?: '' }}<br>
                        {{ get_setting('contact_phone') ?: '' }}
                    </div>
                </div>
                <div class="invoice-meta">
                    <h2 style="margin:0;">Invoice</h2>
                    <div># {{ $invoice->invoice_no }}</div>
                    <div class="badge-status status-{{ $status }}">{{ $status }}</div>
                    <div style="margin-top:10px; font-size: 12px;">
                        <div>Invoice Date: {{ $invoice->invoice_date }}</div>
                        <div>Invoice Due Date: {{ $invoice->due_date ?? '-' }}</div>
                        <div>Invoice Header: {{ $invoice->invoiceHeader?->header_name ?? '-' }}</div>
                        <div>Link To: {{ $invoice->link_to_type ? $invoice->link_to_type . ' - ' . $linkToLabel : '-' }}</div>
                        <div>Charge To: {{ $invoice->charge_to_type ? $invoice->charge_to_type . ' - ' . $chargeToLabel : '-' }}</div>
                        <div>Bank Account: {{ $bankAccountLabel }}</div>
                    </div>
                    <a href="{{ route($routeName . '.pdf', $invoice->id) }}" class="btn-pdf" style="margin-top:10px;" target="_blank">
                        Print PDF
                    </a>
                </div>
            </div>
            @if($invoice->invoiceHeader)
                <div class="billto mt-12">
                    <strong>Invoice Header:</strong>
                    <div>{{ $invoice->invoiceHeader->header_name }}</div>
                    <div style="font-size:12px;color:#4b5563;">Reference: {{ $invoice->invoiceHeader->unique_reference_number }}</div>
                    <div style="font-size:12px;color:#4b5563;">{{ $invoice->invoiceHeader->header_description ?: '-' }}</div>
                </div>
            @endif
            <div class="billto">
                <strong>Bill To:</strong>
                <div>{{ optional($customer)->name ?? 'N/A' }}</div>
                <div style="font-size:12px;color:#4b5563;">{{ optional($customer)->email }}</div>
            </div>
            <div class="billto mt-12">
                <strong>Linking Details:</strong>
                <div>Link To: {{ $invoice->link_to_type ? $invoice->link_to_type . ' - ' . $linkToLabel : '-' }}</div>
                <div>Charge To: {{ $invoice->charge_to_type ? $invoice->charge_to_type . ' - ' . $chargeToLabel : '-' }}</div>
                <div>Bank Account: {{ $bankAccountLabel }}</div>
            </div>
        </div>

        <div style="padding: 18px 24px;">
            <table class="table-items">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Item</th>
                        <th>Description</th>
                        <th style="width:70px;" class="text-right">Qty</th>
                        <th style="width:90px;" class="text-right">Rate</th>
                        <th style="width:90px;" class="text-right">Discount</th>
                        <th style="width:90px;" class="text-right">Tax</th>
                        <th style="width:110px;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $idx => $row)
                        @php
                            $lineBase = max(0, ($row->quantity * $row->rate) - ($row->discount ?? 0));
                            $lineTotal = $lineBase + ($row->tax_amount ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $row->item_name }}</td>
                            <td>{{ $row->description }}</td>
                            <td class="text-right">{{ number_format($row->quantity, 2) }}</td>
                            <td class="text-right">{{ number_format($row->rate, 2) }}</td>
                            <td class="text-right">{{ number_format($row->discount ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($row->tax_amount ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($lineTotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals mt-12">
                <tr><th>Subtotal</th><td class="text-right">{{ number_format($subtotal, 2) }}</td></tr>
                <tr><th>Tax Total</th><td class="text-right">{{ number_format($taxTotal, 2) }}</td></tr>
                <tr><th>Grand Total</th><td class="text-right">{{ number_format($total, 2) }}</td></tr>
                <tr><th>Paid</th><td class="text-right">{{ number_format($paid, 2) }}</td></tr>
                <tr>
                    <th>Penalty Applied Amount</th>
                    <td class="text-right">
                        {{ number_format((float) ($invoice->penalty_amount_applied ?? 0), 2) }}
                    </td>
                </tr>
                <tr><th>Balance</th><td class="text-right">{{ number_format($balance, 2) }}</td></tr>
            </table>

            @php
                $penaltyEnabled = (bool) ($invoice->penalty_enabled ?? false);
                $penaltyAppliedAt = $invoice->penalty_applied_at ?? null;
                $penaltyAppliedAmount = (float) ($invoice->penalty_amount_applied ?? 0);
            @endphp
            <div class="notes-box mt-12">
                <strong>Penalty / Late Payment:</strong>
                <div style="margin-top:6px;font-size:13px;color:#4b5563;">
                    <div>Enabled: {{ $penaltyEnabled ? 'Yes' : 'No' }}</div>
                    @if($penaltyEnabled)
                        <div>Type: {{ $invoice->penalty_type === 'flat_rate' ? 'Flat Rate' : 'Percentage' }}</div>
                        <div>Fixed Rate / Percent: {{ number_format((float) ($invoice->penalty_fixed_rate ?? 0), 2) }}</div>
                        <div>Grace Days: {{ (int) ($invoice->penalty_grace_days ?? 0) }}</div>
                        <div>Max Amount: {{ $invoice->penalty_max_amount !== null ? number_format((float) $invoice->penalty_max_amount, 2) : '-' }}</div>
                        <div>Penalty GL Account ID: {{ $invoice->penalty_gl_account_id ?? '-' }}</div>
                    @endif
                    <div>Applied At: {{ $penaltyAppliedAt ? $penaltyAppliedAt->format('d/m/Y') : '-' }}</div>
                    <div>Applied Amount: {{ $penaltyAppliedAmount ? number_format($penaltyAppliedAmount, 2) : '-' }}</div>
                </div>
            </div>

            <div class="notes-box mt-12">
                <strong>Notes:</strong>
                <div style="margin-top:4px;">{{ $invoice->notes ?? 'N/A' }}</div>
            </div>

            <div class="mt-5">
                <div class="d-flex justify-content-between">
                    <h5 class="m-3">Payments</h5>
                    @if(($balance ?? 0) > 0)
                        <button type="button" class="btn-stripe" data-bs-toggle="modal" data-bs-target="#stripePayModal">
                            Pay with Stripe (Test)
                        </button>
                        {{-- @if(($customer->available_credit ?? 0) > 0)
                            <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#applyCreditModal">
                                Apply Credit
                            </button>
                        @endif --}}
                    @endif
                </div>
                <table class="table-items">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Bank</th>
                            <th class="text-right">Amount</th>
                            <th>Notes</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments as $pidx => $pay)
                            <tr>
                                <td>{{ $pidx + 1 }}</td>
                                <td>{{ $pay->payment_date }}</td>
                                <td>{{ optional($pay->paymentMethod)->name ?? '-' }}</td>
                                <td>{{ optional($pay->bankAccount)->account_name ?? '-' }}</td>
                                <td class="text-right">{{ number_format($pay->amount, 2) }}</td>
                                <td>{{ $pay->notes }}</td>
                                <td class="text-center">
                                    @if($pay->is_voided)
                                        <span class="badge bg-secondary">Voided</span>
                                    @elseif($pay->source_receipt_id)
                                        <form action="{{ route($routeName . '.undoCredit', [$invoice->id, $pay->id]) }}" method="POST" onsubmit="return confirm('Undo this credit application?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Undo</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(($balance ?? 0) > 0)
    <div class="modal fade" id="stripePayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stripe Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route($routeName . '.pay', $invoice->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" min="0.50" step="0.01" max="{{ $balance }}" value="{{ $balance }}" required>
                            <small class="text-muted">Max: {{ number_format($balance, 2) }} (current balance)</small>
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

@if(($customer->available_credit ?? 0) > 0 && ($balance ?? 0) > 0)
    <div class="modal fade" id="applyCreditModal" tabindex="-1" aria-hidden="true" data-credit-modal data-balance="{{ $balance }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply Credits - {{ $invoice->invoice_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route($routeName . '.applyCredit', $invoice->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($credits->isEmpty())
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
                                        @foreach($credits as $idx => $credit)
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
                                                        max="{{ min($credit->remaining_amount, $balance) }}"
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
                                <div><strong>Balance Due:</strong> <span data-credit-remaining>{{ number_format($balance, 2) }}</span></div>
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

        inputs.forEach((input) => input.addEventListener('input', (e) => {
            const maxAvail = parseFloat(input.dataset.available || '0');
            let val = parseFloat(input.value || '0') || 0;

            if (val > maxAvail) val = maxAvail;
            if (val < 0) val = 0;

            // enforce invoice balance across all rows
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
