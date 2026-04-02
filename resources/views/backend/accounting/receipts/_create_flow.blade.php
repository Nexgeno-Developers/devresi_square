{{-- Focused receipt-capture flow with invoice search, amount guard, and push toggles --}}

@php($currentUserId = old('user_id', data_get($item ?? null, 'user_id')))
@php($currentBankId = old('sys_bank_account_id', data_get($item ?? null, 'sys_bank_account_id')))
@php($currentMethodId = old('payment_method_id', data_get($item ?? null, 'payment_method_id')))
@php($notify = old('payment_meta.notify', data_get($item ?? null, 'payment_meta.notify', [])))
@php($currentNotes = old('notes', data_get($item ?? null, 'notes')))
@php($pound = getPoundSymbol())

<div class="flow-grid">
    <div class="flow-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="flow-card__title mb-0">1) Find Invoice</div>
                <small class="text-dim">Search by number, name, tenancy, or outstanding only.</small>
            </div>
            <span id="outstanding-badge" class="badge badge-outstanding d-none"></span>
        </div>
        <select id="invoice_id" class="form-select" data-placeholder="Search invoice">
            <option value="">Select invoice (optional)</option>
            @if(isset($item) && ($item->receiptable_type === 'sale_invoice') && $item->receiptable)
                @php($inv = $item->receiptable)
                @php($preText = ($inv->invoice_no ?? $inv->id) . ' — ' . $pound . number_format($inv->total_amount ?? 0, 2))
                <option value="{{ $inv->id }}" selected data-outstanding="{{ $inv->balance_amount ?? 0 }}" data-user="{{ $inv->user_id ?? '' }}">{{ $preText }}</option>
            @endif
        </select>
    </div>

    <div class="flow-card">
        <div class="flow-card__title">2) Receipt Details</div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">Date of Receipt <span class="text-danger">*</span></label>
                <input type="date" name="receipt_date" id="receipt_date" class="form-control" required value="{{ old('receipt_date', $defaults['receipt_date'] ?? now()->toDateString()) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" required value="{{ old('amount', $defaults['amount'] ?? '') }}">
                <small class="text-dim">If an invoice is selected, amount cannot exceed its outstanding.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select name="user_id" id="user_id" class="form-select" required>
                    <option value="">Select customer</option>
                    @foreach($selectOptions['user_id'] as $id => $name)
                        <option value="{{ $id }}" @selected((string)$currentUserId === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                <select name="sys_bank_account_id" id="sys_bank_account_id" class="form-select" required>
                    <option value="">Select bank</option>
                    @foreach($selectOptions['sys_bank_account_id'] as $id => $label)
                        <option value="{{ $id }}" @selected((string)$currentBankId === (string)$id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                <select name="payment_method_id" id="payment_method_id" class="form-select" required>
                    <option value="">Select method</option>
                    @foreach($selectOptions['payment_method_id'] as $id => $label)
                        <option value="{{ $id }}" @selected((string)$currentMethodId === (string)$id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3" id="method-details" style="display:none;">
            <div class="flow-card">
                <div class="flow-card__title mb-2">Method Details</div>
                <div class="row g-2" id="method-fields">
                    {{-- dynamic fields injected by JS --}}
                </div>
            </div>
        </div>
    </div>

    <div class="flow-card flow-card--accent">
        <div class="flow-card__title">3) Push Notification?</div>
        <div class="text-dim mb-2">Choose channels to notify the customer.</div>
        <div class="row g-2">
            @php($channels = [
                ['key' => 'notify_email', 'label' => 'Email'],
                ['key' => 'notify_sms', 'label' => 'SMS'],
                ['key' => 'notify_whatsapp', 'label' => 'WhatsApp'],
            ])
            @foreach($channels as $ch)
                @php($notifyKey = $ch['key'] === 'notify_email' ? 'email' : ($ch['key'] === 'notify_sms' ? 'sms' : 'whatsapp'))
                <div class="col-md-12">
                    <label class="form-label d-block">{{ $ch['label'] }}</label>
                    <div class="btn-group toggle-yesno" role="group">
                        <input type="radio" class="btn-check" name="{{ $ch['key'] }}" id="{{ $ch['key'] }}_yes" value="yes" data-label="{{ $ch['label'] }}" {{ (old($ch['key']) === 'yes' || ($notify[$notifyKey] ?? false)) ? 'checked' : '' }}>
                        <label class="btn btn-light" for="{{ $ch['key'] }}_yes">YES</label>

                        <input type="radio" class="btn-check" name="{{ $ch['key'] }}" id="{{ $ch['key'] }}_no" value="no" data-label="{{ $ch['label'] }}" {{ (old($ch['key']) === 'no' || (($notify[$notifyKey] ?? null) === false)) ? 'checked' : '' }}>
                        <label class="btn btn-outline-light" for="{{ $ch['key'] }}_no">NO</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flow-card">
        <div class="flow-card__title">4) Notes</div>
        <textarea name="notes" id="notes" rows="4" class="form-control" placeholder="Internal notes or delivery info">{{ $currentNotes }}</textarea>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger mt-3">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-primary">Save Receipt</button>
    <a href="{{ route($routeName . '.index') }}" class="btn btn-secondary">Cancel</a>
</div>
