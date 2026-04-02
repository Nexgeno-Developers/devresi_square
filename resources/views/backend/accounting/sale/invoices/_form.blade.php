@csrf
@if(isset($item))
    @method('PUT')
@endif

@php
    $oldVal = fn($key, $default = null) => old(
        $key,
        data_get($item ?? null, $key, data_get($defaults ?? [], $key, $default))
    );
    $users = $selectOptions['user_id'] ?? [];
    $linkToTypes = $selectOptions['link_to_type'] ?? [];
    $chargeToTypes = $selectOptions['charge_to_type'] ?? [];
    $linkToPropertyOptions = $selectOptions['link_to_property'] ?? [];
    $linkToTenancyOptions = $selectOptions['link_to_tenancy'] ?? [];
    $linkToContractorOptions = $selectOptions['link_to_contractor'] ?? [];
    $chargeToOwnerOptions = $selectOptions['charge_to_owner'] ?? [];
    $chargeToTenantOptions = $selectOptions['charge_to_tenant'] ?? [];
    $chargeToContractorOptions = $selectOptions['charge_to_contractor'] ?? [];
    $bankAccountOptions = $selectOptions['bank_account_id'] ?? [];
    $selectedInvoiceHeader = $selectedInvoiceHeader ?? null;
    $isRecurringChild = isset($item) && !empty($item->recurring_master_invoice_id);
    $recurringFromItem = $isRecurringChild || isset($item)
        ? (
            !empty($item->recurring_month_interval)
                ? (string) $item->recurring_month_interval
                : (!empty($item->recurring_custom_unit) ? 'custom' : '0')
        )
        : '0';
    $recurringSelectValue = (string) old('recurring', $recurringFromItem);
    $repeatEveryCustomValue = (int) old('repeat_every_custom', $item->recurring_custom_interval ?? 1);
    $repeatTypeCustomValue = (string) old('repeat_type_custom', $item->recurring_custom_unit ?? 'month');
    $recurringCyclesValue = (int) old('recurring_cycles', $item->recurring_cycles ?? 6);
    $unlimitedCyclesChecked = (bool) old('unlimited_cycles', $item->unlimited_cycles ?? false);
    $statuses = ['draft' => 'Draft', 'issued' => 'Issued', 'paid' => 'Paid', 'partial' => 'Partial', 'cancelled' => 'Cancelled'];
    $taxes = $selectOptions['tax_id'] ?? [];
    $taxRatesMap = $selectOptions['tax_rates'] ?? [];
    $penaltyAppliedAt = isset($item) ? ($item->penalty_applied_at ?? null) : null;
    $penaltyEnabledChecked = (bool) old('penalty_enabled', $item->penalty_enabled ?? false);
    $penaltyTypeValue = (string) old('penalty_type', $item->penalty_type ?? 'percentage');
    $penaltyFixedRateValue = old('penalty_fixed_rate', $item->penalty_fixed_rate ?? null);
    $penaltyGraceDaysValue = (int) old('penalty_grace_days', $item->penalty_grace_days ?? 0);
    $penaltyMaxAmountValue = old('penalty_max_amount', $item->penalty_max_amount ?? null);
    $penaltyGlAccountOptions = $selectOptions['penalty_gl_account_id'] ?? [];
    $penaltyGlAccountSelected = old('penalty_gl_account_id', $item->penalty_gl_account_id ?? null);
    $existingItems = old('items', isset($item) ? $item->items->map(function($it){
        return [
            'item_name' => $it->item_name,
            'description' => $it->description,
            'quantity' => $it->quantity,
            'rate' => $it->rate,
            'discount' => $it->discount,
            'tax_id' => $it->tax_id,
            'tax_rate' => $it->tax_rate,
            'tax_amount' => $it->tax_amount,
            'line_total' => $it->line_total,
            'notes' => $it->notes,
        ];
    })->toArray() : []);
    if (empty($existingItems)) {
        $existingItems = [
            ['item_name' => '', 'description' => '', 'quantity' => 1, 'rate' => 0, 'discount' => 0, 'tax_id' => null, 'tax_rate' => 0, 'tax_amount' => 0, 'line_total' => 0, 'notes' => '']
        ];
    }
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-body">
        @if(isset($item))
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Invoice No</label>
                    <input type="text" name="invoice_no" class="form-control" value="{{ $oldVal('invoice_no') }}" readonly>
                </div>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Link To Type</label>
                <select name="link_to_type" id="link-to-type" class="form-select">
                    <option value="">Select</option>
                    @foreach($linkToTypes as $value => $label)
                        <option value="{{ $value }}" {{ $oldVal('link_to_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Link To</label>
                <input type="hidden" name="link_to_id" id="link-to-id" value="{{ $oldVal('link_to_id') }}">

                <div class="entity-select-wrap" data-entity-group="link_to" data-entity-type="Property">
                    <select id="link-to-property-select" class="form-select entity-select">
                        <option value="">Select property</option>
                        @foreach($linkToPropertyOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('link_to_type') === 'Property' && (string)$oldVal('link_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="entity-select-wrap d-none" data-entity-group="link_to" data-entity-type="Tenancy">
                    <select id="link-to-tenancy-select" class="form-select entity-select">
                        <option value="">Select tenancy</option>
                        @foreach($linkToTenancyOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('link_to_type') === 'Tenancy' && (string)$oldVal('link_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="entity-select-wrap d-none" data-entity-group="link_to" data-entity-type="Contractor">
                    <select id="link-to-contractor-select" class="form-select entity-select">
                        <option value="">Select contractor</option>
                        @foreach($linkToContractorOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('link_to_type') === 'Contractor' && (string)$oldVal('link_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Charge To Type</label>
                <select name="charge_to_type" id="charge-to-type" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($chargeToTypes as $value => $label)
                        <option value="{{ $value }}" {{ $oldVal('charge_to_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Charge To</label>
                <input type="hidden" name="charge_to_id" id="charge-to-id" value="{{ $oldVal('charge_to_id') }}" required>

                <div class="entity-select-wrap" data-entity-group="charge_to" data-entity-type="Owner">
                    <select id="charge-to-owner-select" class="form-select entity-select">
                        <option value="">Select owner</option>
                        @foreach($chargeToOwnerOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('charge_to_type') === 'Owner' && (string)$oldVal('charge_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="entity-select-wrap d-none" data-entity-group="charge_to" data-entity-type="Tenant">
                    <select id="charge-to-tenant-select" class="form-select entity-select">
                        <option value="">Select tenant</option>
                        @foreach($chargeToTenantOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('charge_to_type') === 'Tenant' && (string)$oldVal('charge_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="entity-select-wrap d-none" data-entity-group="charge_to" data-entity-type="Contractor">
                    <select id="charge-to-contractor-select" class="form-select entity-select">
                        <option value="">Select contractor</option>
                        @foreach($chargeToContractorOptions as $id => $label)
                            <option value="{{ $id }}" {{ $oldVal('charge_to_type') === 'Contractor' && (string)$oldVal('charge_to_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Invoice Header</label>
                <select name="invoice_header_id" id="invoice-header-id" class="form-select">
                    <option value="">Select invoice header</option>
                    @if($selectedInvoiceHeader)
                        <option value="{{ $selectedInvoiceHeader->id }}" selected>
                            {{ $selectedInvoiceHeader->header_name }} ({{ $selectedInvoiceHeader->unique_reference_number }})
                        </option>
                    @endif
                </select>
                <small class="text-muted">Search active invoice headers by name or reference number.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ $oldVal('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label">Recurring Invoice?</label>
                @php
                    $recurringChildTooltip = 'You cannot set this invoice as recurring because this invoice is child from another recurring invoice.';
                @endphp
                <span class="d-inline-block" {{ $isRecurringChild ? 'data-bs-toggle="tooltip" data-title="' . $recurringChildTooltip . '" title="' . $recurringChildTooltip . '"' : '' }}>
                    <select
                        name="recurring"
                        id="recurring-type"
                        class="form-select"
                        {{ $isRecurringChild ? 'disabled' : '' }}
                    >
                        <option value="0" {{ $recurringSelectValue === '0' ? 'selected' : '' }}>No</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $recurringSelectValue === (string)$m ? 'selected' : '' }}>
                                Every {{ $m }} month(s)
                            </option>
                        @endfor
                        <option value="custom" {{ $recurringSelectValue === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </span>
                <small class="text-muted">Generates future invoices automatically.</small>
            </div>

            <div class="col-md-3 {{ $recurringSelectValue === '0' ? 'd-none' : '' }}" id="recurring-cycles-wrapper">
                <label class="form-label">Cycles</label>
                <input
                    type="number"
                    min="1"
                    name="recurring_cycles"
                    id="recurring-cycles"
                    class="form-control"
                    value="{{ $recurringCyclesValue }}"
                    {{ $isRecurringChild ? 'disabled' : '' }}
                >
            </div>

            <div class="col-md-3 {{ $recurringSelectValue === '0' ? 'd-none' : '' }}" id="recurring-unlimited-wrapper">
                <label class="form-label d-flex align-items-center gap-2 mt-2">
                    <input
                        type="checkbox"
                        name="unlimited_cycles"
                        id="unlimited-cycles"
                        value="1"
                        class="form-check-input"
                        {{ $unlimitedCyclesChecked ? 'checked' : '' }}
                        {{ $isRecurringChild ? 'disabled' : '' }}
                    >
                    Unlimited
                </label>
            </div>
        </div>

        <div class="row g-3 mt-2 {{ $recurringSelectValue === 'custom' ? '' : 'd-none' }}" id="recurring-custom-fields">
            <div class="col-md-6">
                <label class="form-label">Repeat Every (Custom)</label>
                <input
                    type="number"
                    min="1"
                    name="repeat_every_custom"
                    id="repeat-every-custom"
                    class="form-control"
                    value="{{ $repeatEveryCustomValue }}"
                    {{ $isRecurringChild ? 'disabled' : '' }}
                >
            </div>
            <div class="col-md-6">
                <label class="form-label">Repeat Type (Custom)</label>
                <select
                    name="repeat_type_custom"
                    id="repeat-type-custom"
                    class="form-select"
                    {{ $isRecurringChild ? 'disabled' : '' }}
                >
                    <option value="day" {{ $repeatTypeCustomValue === 'day' ? 'selected' : '' }}>Day(s)</option>
                    <option value="week" {{ $repeatTypeCustomValue === 'week' ? 'selected' : '' }}>Week(s)</option>
                    <option value="month" {{ $repeatTypeCustomValue === 'month' ? 'selected' : '' }}>Month(s)</option>
                    <option value="year" {{ $repeatTypeCustomValue === 'year' ? 'selected' : '' }}>Year(s)</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                <input type="date" name="invoice_date" id="invoice-date" class="form-control" value="{{ $oldVal('invoice_date') ?? now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" id="due-date" class="form-control" value="{{ $oldVal('due_date') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Discount Type</label>
                <select name="discount_type" id="discount-type" class="form-select">
                    <option value="" {{ $oldVal('discount_type') === '' ? 'selected' : '' }}>No discount</option>
                    <option value="before_tax" {{ $oldVal('discount_type') === 'before_tax' ? 'selected' : '' }}>Before Tax</option>
                    <option value="after_tax" {{ $oldVal('discount_type') === 'after_tax' ? 'selected' : '' }}>After Tax</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mt-2" id="penalty-config-wrapper">
            <div class="col-md-3">
                <label class="form-label">Penalty / Late Payment</label>
                <div class="form-check mt-2">
                    <input
                        type="checkbox"
                        name="penalty_enabled"
                        id="penalty-enabled"
                        value="1"
                        class="form-check-input"
                        {{ $penaltyEnabledChecked ? 'checked' : '' }}
                        {{ !empty($penaltyAppliedAt) ? 'disabled' : '' }}
                    >
                    <label class="form-check-label" for="penalty-enabled">Enable</label>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Penalty Type</label>
                <select
                    name="penalty_type"
                    id="penalty-type"
                    class="form-select"
                    {{ !empty($penaltyAppliedAt) || !$penaltyEnabledChecked ? 'disabled' : '' }}
                >
                    <option value="percentage" {{ $penaltyTypeValue === 'percentage' ? 'selected' : '' }}>Is in %</option>
                    <option value="flat_rate" {{ $penaltyTypeValue === 'flat_rate' ? 'selected' : '' }}>Is in Flat Rate</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Fixed Rate / Percent</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="penalty_fixed_rate"
                    id="penalty-fixed-rate"
                    class="form-control"
                    value="{{ $penaltyFixedRateValue }}"
                    {{ !empty($penaltyAppliedAt) || !$penaltyEnabledChecked ? 'disabled' : '' }}
                >
                <small class="text-muted d-block">
                    {{ $penaltyTypeValue === 'percentage' ? 'Enter percent (0-100+).' : 'Enter flat amount in currency.' }}
                </small>
            </div>

        </div>

        <div class="row g-3 mt-1 {{ !$penaltyEnabledChecked && empty($penaltyAppliedAt) ? 'd-none' : '' }}" id="penalty-extra-fields">
            <div class="col-md-3">
                <label class="form-label">Grace Days</label>
                <input
                    type="number"
                    min="0"
                    name="penalty_grace_days"
                    id="penalty-grace-days"
                    class="form-control"
                    value="{{ $penaltyGraceDaysValue }}"
                    {{ !empty($penaltyAppliedAt) || !$penaltyEnabledChecked ? 'disabled' : '' }}
                >
            </div>

            <div class="col-md-3">
                <label class="form-label">Penalty Max Amount</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="penalty_max_amount"
                    id="penalty-max-amount"
                    class="form-control"
                    value="{{ $penaltyMaxAmountValue }}"
                    {{ !empty($penaltyAppliedAt) || !$penaltyEnabledChecked ? 'disabled' : '' }}
                >
            </div>

            <div class="col-md-6">
                <label class="form-label">Penalty GL Account (optional)</label>
                <select
                    name="penalty_gl_account_id"
                    id="penalty-gl-account-id"
                    class="form-select"
                    {{ !empty($penaltyAppliedAt) || !$penaltyEnabledChecked ? 'disabled' : '' }}
                >
                    <option value="">Use revenue account</option>
                    @foreach($penaltyGlAccountOptions as $id => $label)
                        <option value="{{ $id }}" {{ (string)$penaltyGlAccountSelected === (string)$id ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(!empty($penaltyAppliedAt))
            <div class="alert alert-info mt-2 mb-0">
                Penalty already applied on {{ formatDate($penaltyAppliedAt) }}
            </div>
        @endif

        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Bank Account</label>
                <select name="bank_account_id" class="form-select">
                    <option value="">Select bank account</option>
                    @foreach($bankAccountOptions as $id => $label)
                        <option value="{{ $id }}" {{ (string)$oldVal('bank_account_id') === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">This stores the bank account selected for the charge-to entity.</small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div id="invoice-header-preview" class="alert alert-light border mb-0 {{ $selectedInvoiceHeader ? '' : 'd-none' }}">
                    <div><strong>Header Name:</strong> <span data-header-name>{{ $selectedInvoiceHeader->header_name ?? '' }}</span></div>
                    <div><strong>Reference:</strong> <span data-header-reference>{{ $selectedInvoiceHeader->unique_reference_number ?? '' }}</span></div>
                    <div><strong>Description:</strong> <span data-header-description>{{ $selectedInvoiceHeader->header_description ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div id="property-context-box" class="alert alert-info border mb-0 d-none">
                    <div class="fw-semibold mb-2">Property Context</div>
                    <div><strong>Property:</strong> <span data-property-name>-</span></div>
                    <div><strong>Address:</strong> <span data-property-address>-</span></div>
                    <div><strong>Owner:</strong> <span data-property-owners>-</span></div>
                    <div><strong>Tenant:</strong> <span data-property-tenants>-</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3 d-none">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="total_amount" class="form-control" value="{{ $oldVal('total_amount') }}" required readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Balance Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="balance_amount" class="form-control" value="{{ $oldVal('balance_amount') }}" required readonly>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2"><span class="bi bi-list"></span> Invoice Items</h5>
            <button type="button" class="btn btn-sm btn-success" id="add-item-row">+ Add Item</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="items-table">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width:220px;">Item Name</th>
                        <th>Description</th>
                        <th style="width:120px;">Unit Price</th>
                        <th style="width:100px;">Quantity</th>
                        <th style="width:150px;">Tax</th>
                        <th style="width:110px;">Tax Rate (%)</th>
                        <th style="width:120px;">Tax Amount</th>
                        <th style="width:140px;">Total (Incl. Tax)</th>
                        <th style="width:70px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingItems as $index => $row)
                    <tr>
                        <td>
                            <input type="text" name="items[{{ $index }}][item_name]" class="form-control item-name" value="{{ $row['item_name'] }}" required>
                            <input type="hidden" name="items[{{ $index }}][discount]" class="discount" value="{{ $row['discount'] }}">
                        </td>
                        <td><input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $row['description'] }}"></td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control rate text-end" value="{{ $row['rate'] }}"></td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][quantity]" class="form-control qty text-end" value="{{ $row['quantity'] }}"></td>
                        <td>
                            <select name="items[{{ $index }}][tax_id]" class="form-select tax-id">
                                <option value="">None</option>
                                @foreach($taxes as $id => $name)
                                    <option value="{{ $id }}" data-rate="{{ $taxRatesMap[$id] ?? 0 }}" {{ (string)$row['tax_id'] === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_rate]" class="form-control tax-rate text-end" value="{{ $row['tax_rate'] }}"></td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_amount]" class="form-control tax-amount text-end" value="{{ $row['tax_amount'] }}" readonly></td>
                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][line_total]" class="form-control line-total text-end" value="{{ $row['line_total'] }}" readonly></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="6" class="text-end">Subtotal:</th>
                        <th colspan="2"><input type="text" class="form-control text-end" id="subtotal-display" readonly></th>
                    </tr>
                    <tr class="table-light">
                        <th colspan="6" class="text-end">Tax Total:</th>
                        <th colspan="2"><input type="text" class="form-control text-end" id="taxtotal-display" readonly></th>
                    </tr>
                    <tr class="table-light">
                        <th colspan="6" class="text-end align-middle">Discount:</th>
                        <th colspan="2">
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="discount_value" id="discount-value" class="form-control text-end" value="{{ $oldVal('discount_value', 0) }}">
                                <select name="discount_mode" id="discount-mode" class="form-select" style="max-width: 150px;">
                                    <option value="percent" {{ $oldVal('discount_mode', 'percent') === 'percent' ? 'selected' : '' }}>%</option>
                                    <option value="fixed" {{ $oldVal('discount_mode', 'percent') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                </select>
                            </div>
                            <input type="hidden" name="discount_amount" id="discount-amount" value="{{ $oldVal('discount_amount', 0) }}">
                        </th>
                    </tr>
                    <tr class="table-light">
                        <th colspan="6" class="text-end">Discount Amount:</th>
                        <th colspan="2"><input type="text" class="form-control text-end" id="discount-amount-display" readonly></th>
                    </tr>
                    <tr class="table-primary">
                        <th colspan="6" class="text-end">Grand Total:</th>
                        <th colspan="2"><input type="text" class="form-control text-end fw-bold" id="grandtotal-display" readonly></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3">{{ $oldVal('notes') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route($routeName . '.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const invoiceDateInput = document.getElementById('invoice-date');
    const dueDateInput = document.getElementById('due-date');
    const invoiceHeaderSelect = $('#invoice-header-id');
    const invoiceHeaderPreview = document.getElementById('invoice-header-preview');
    const headerName = invoiceHeaderPreview?.querySelector('[data-header-name]');
    const headerReference = invoiceHeaderPreview?.querySelector('[data-header-reference]');
    const headerDescription = invoiceHeaderPreview?.querySelector('[data-header-description]');
    const headerSearchUrl = "{{ route('backend.accounting.masters.invoice_headers.search') }}";
    const headerJsonUrl = (id) => "{{ route('backend.accounting.masters.invoice_headers.json', ['invoiceHeader' => '___ID___']) }}".replace('___ID___', id);
    const propertyContextUrl = (id) => "{{ route('backend.accounting.sale.invoices.propertyContext', ['property' => '___ID___']) }}".replace('___ID___', id);
    const linkToTypeInput = document.getElementById('link-to-type');
    const linkToIdInput = document.getElementById('link-to-id');
    const chargeToTypeInput = document.getElementById('charge-to-type');
    const chargeToIdInput = document.getElementById('charge-to-id');
    const linkToPropertySelect = document.getElementById('link-to-property-select');
    const chargeToOwnerSelect = document.getElementById('charge-to-owner-select');
    const chargeToTenantSelect = document.getElementById('charge-to-tenant-select');
    const propertyContextBox = document.getElementById('property-context-box');
    const propertyNameNode = propertyContextBox?.querySelector('[data-property-name]');
    const propertyAddressNode = propertyContextBox?.querySelector('[data-property-address]');
    const propertyOwnersNode = propertyContextBox?.querySelector('[data-property-owners]');
    const propertyTenantsNode = propertyContextBox?.querySelector('[data-property-tenants]');
    const originalOwnerOptions = chargeToOwnerSelect?.innerHTML || '';
    const originalTenantOptions = chargeToTenantSelect?.innerHTML || '';
    const ownerTypeOption = chargeToTypeInput?.querySelector('option[value="Owner"]');
    const tenantTypeOption = chargeToTypeInput?.querySelector('option[value="Tenant"]');

    const recurringSelect = document.getElementById('recurring-type');
    const recurringCustomFields = document.getElementById('recurring-custom-fields');
    const recurringCyclesWrapper = document.getElementById('recurring-cycles-wrapper');
    const recurringUnlimitedWrapper = document.getElementById('recurring-unlimited-wrapper');
    const recurringCyclesInput = document.getElementById('recurring-cycles');
    const unlimitedCyclesCheckbox = document.getElementById('unlimited-cycles');

    function syncRecurringFields() {
        if (!recurringSelect) return;

        const mode = recurringSelect.value || '0';
        const isNone = mode === '0';
        const isCustom = mode === 'custom';

        if (recurringCustomFields) recurringCustomFields.classList.toggle('d-none', !isCustom);
        if (recurringCyclesWrapper) recurringCyclesWrapper.classList.toggle('d-none', isNone);
        if (recurringUnlimitedWrapper) recurringUnlimitedWrapper.classList.toggle('d-none', isNone);

        if (recurringCyclesInput && unlimitedCyclesCheckbox) {
            if (isNone) {
                unlimitedCyclesCheckbox.checked = false;
                unlimitedCyclesCheckbox.disabled = true;
                recurringCyclesInput.disabled = true;
            } else {
                unlimitedCyclesCheckbox.disabled = false;
                recurringCyclesInput.disabled = unlimitedCyclesCheckbox.checked;
            }
        }
    }

    recurringSelect?.addEventListener('change', syncRecurringFields);
    unlimitedCyclesCheckbox?.addEventListener('change', syncRecurringFields);
    syncRecurringFields();

    function setDueDate() {
        if (!invoiceDateInput.value) return;
        const d = new Date(invoiceDateInput.value);
        d.setDate(d.getDate() + 30);
        dueDateInput.value = d.toISOString().split('T')[0];
    }

    invoiceDateInput.addEventListener('change', setDueDate);

    if (!dueDateInput.value && invoiceDateInput.value) {
        setDueDate();
    }

    if (invoiceHeaderSelect.length) {
        invoiceHeaderSelect.select2({
            placeholder: 'Search invoice header',
            allowClear: true,
            ajax: {
                url: headerSearchUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({
                    results: (data.results || []).map(row => ({
                        id: row.id,
                        text: row.text,
                        header_name: row.header_name,
                        unique_reference_number: row.unique_reference_number,
                        header_description: row.header_description
                    }))
                })
            },
            minimumInputLength: 0
        });

        invoiceHeaderSelect.on('select2:select', async (e) => {
            const selected = e.params.data;
            hydrateHeaderPreview(selected);

            try {
                const res = await fetch(headerJsonUrl(selected.id));
                if (res.ok) {
                    const full = await res.json();
                    hydrateHeaderPreview(full);
                }
            } catch (err) {
                console.warn('Invoice header fetch failed', err);
            }
        });

        invoiceHeaderSelect.on('select2:clear', () => {
            clearHeaderPreview();
        });
    }

    function setupEntitySelector(groupName, typeInput, hiddenIdInput) {
        const wrappers = Array.from(document.querySelectorAll(`[data-entity-group="${groupName}"]`));

        function syncVisibleSelect() {
            const currentType = typeInput?.value || '';

            wrappers.forEach(wrapper => {
                const isActive = wrapper.dataset.entityType === currentType;
                const select = wrapper.querySelector('select');

                wrapper.classList.toggle('d-none', !isActive);
                if (select) {
                    select.disabled = !isActive;
                }
            });

            const activeWrapper = wrappers.find(wrapper => wrapper.dataset.entityType === currentType);
            const activeSelect = activeWrapper?.querySelector('select');
            hiddenIdInput.value = activeSelect?.value || '';
        }

        wrappers.forEach(wrapper => {
            const select = wrapper.querySelector('select');
            if (!select) {
                return;
            }

            select.addEventListener('change', () => {
                if (!wrapper.classList.contains('d-none')) {
                    hiddenIdInput.value = select.value || '';
                }
            });
        });

        typeInput?.addEventListener('change', syncVisibleSelect);
        syncVisibleSelect();
    }

    setupEntitySelector('link_to', linkToTypeInput, linkToIdInput);
    setupEntitySelector('charge_to', chargeToTypeInput, chargeToIdInput);

    function clearPropertyContext() {
        if (propertyContextBox) {
            propertyContextBox.classList.add('d-none');
        }
        if (propertyNameNode) propertyNameNode.textContent = '-';
        if (propertyAddressNode) propertyAddressNode.textContent = '-';
        if (propertyOwnersNode) propertyOwnersNode.textContent = '-';
        if (propertyTenantsNode) propertyTenantsNode.textContent = '-';

        if (chargeToOwnerSelect) {
            chargeToOwnerSelect.innerHTML = originalOwnerOptions;
        }
        if (chargeToTenantSelect) {
            chargeToTenantSelect.innerHTML = originalTenantOptions;
        }
        if (chargeToTypeInput?.value === 'Owner' && chargeToOwnerSelect) {
            chargeToOwnerSelect.value = chargeToIdInput.value || '';
        }
        if (chargeToTypeInput?.value === 'Tenant' && chargeToTenantSelect) {
            chargeToTenantSelect.value = chargeToIdInput.value || '';
        }
        if (ownerTypeOption) {
            ownerTypeOption.disabled = false;
        }
        if (tenantTypeOption) {
            tenantTypeOption.disabled = false;
        }
    }

    function setSelectOptions(select, rows, placeholder, selectedValue = '') {
        if (!select) {
            return;
        }

        const options = [`<option value="">${placeholder}</option>`];

        (rows || []).forEach(row => {
            const selected = String(selectedValue) === String(row.id) ? ' selected' : '';
            options.push(`<option value="${row.id}"${selected}>${row.label || row.name || row.id}</option>`);
        });

        select.innerHTML = options.join('');
    }

    function syncChargeToAvailability(payload) {
        const owners = payload?.owners || [];
        const tenants = payload?.tenants || [];
        const selectedChargeToId = chargeToIdInput?.value || '';

        setSelectOptions(chargeToOwnerSelect, owners, owners.length ? 'Select owner' : 'No owners found', selectedChargeToId);
        setSelectOptions(chargeToTenantSelect, tenants, tenants.length ? 'Select tenant' : 'No tenants found', selectedChargeToId);

        if (ownerTypeOption) {
            ownerTypeOption.disabled = owners.length === 0;
        }
        if (tenantTypeOption) {
            tenantTypeOption.disabled = tenants.length === 0;
        }

        if ((chargeToTypeInput?.value === 'Owner' && owners.length === 0) || (chargeToTypeInput?.value === 'Tenant' && tenants.length === 0)) {
            chargeToTypeInput.value = '';
            chargeToIdInput.value = '';
            chargeToTypeInput.dispatchEvent(new Event('change'));
        } else {
            const activeType = chargeToTypeInput?.value;
            const activeSelect = activeType === 'Owner' ? chargeToOwnerSelect : (activeType === 'Tenant' ? chargeToTenantSelect : null);
            if (activeSelect) {
                chargeToIdInput.value = activeSelect.value || '';
            }
        }
    }

    function renderPropertyContext(payload) {
        if (!payload?.property || !propertyContextBox) {
            clearPropertyContext();
            return;
        }

        propertyContextBox.classList.remove('d-none');
        if (propertyNameNode) {
            const reference = payload.property.reference ? ` (${payload.property.reference})` : '';
            propertyNameNode.textContent = `${payload.property.name}${reference}`;
        }
        if (propertyAddressNode) {
            propertyAddressNode.textContent = payload.property.address || '-';
        }
        if (propertyOwnersNode) {
            propertyOwnersNode.textContent = (payload.owners || []).map(row => row.label || row.name).join(', ') || 'No owner found';
        }
        if (propertyTenantsNode) {
            propertyTenantsNode.textContent = (payload.tenants || []).map(row => row.label || row.name).join(', ') || 'No tenant found';
        }

        syncChargeToAvailability(payload);
    }

    async function loadPropertyContext(propertyId) {
        if (!propertyId || linkToTypeInput?.value !== 'Property') {
            clearPropertyContext();
            return;
        }

        try {
            const response = await fetch(propertyContextUrl(propertyId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Property context request failed with ${response.status}`);
            }

            const payload = await response.json();
            renderPropertyContext(payload);
        } catch (error) {
            console.warn('Property context fetch failed', error);
            clearPropertyContext();
        }
    }

    linkToTypeInput?.addEventListener('change', () => {
        if (linkToTypeInput.value === 'Property' && linkToPropertySelect?.value) {
            loadPropertyContext(linkToPropertySelect.value);
            return;
        }

        clearPropertyContext();
    });

    linkToPropertySelect?.addEventListener('change', () => {
        if (linkToTypeInput?.value === 'Property') {
            loadPropertyContext(linkToPropertySelect.value);
        }
    });

    if (linkToTypeInput?.value === 'Property' && linkToPropertySelect?.value) {
        loadPropertyContext(linkToPropertySelect.value);
    } else {
        clearPropertyContext();
    }

    const tableBody = document.querySelector('#items-table tbody');
    const addBtn = document.querySelector('#add-item-row');
    const totalField = document.querySelector('input[name="total_amount"]');
    const balanceField = document.querySelector('input[name="balance_amount"]');
    const subTotalDisplay = document.getElementById('subtotal-display');
    const taxTotalDisplay = document.getElementById('taxtotal-display');
    const grandTotalDisplay = document.getElementById('grandtotal-display');
    const penaltyAlreadyApplied = @json(!empty($penaltyAppliedAt));
    const penaltyAppliedAmount = @json((float) ($item->penalty_amount_applied ?? 0));
    const discountType = document.getElementById('discount-type');
    const discountValue = document.getElementById('discount-value');
    const discountMode = document.getElementById('discount-mode');
    const discountAmount = document.getElementById('discount-amount');
    const discountAmountDisplay = document.getElementById('discount-amount-display');

    function recalcRow(tr) {
        const qty = parseFloat(tr.querySelector('.qty').value) || 0;
        const rate = parseFloat(tr.querySelector('.rate').value) || 0;
        const discount = parseFloat(tr.querySelector('.discount').value) || 0;
        const taxRate = parseFloat(tr.querySelector('.tax-rate').value) || 0;
        const base = Math.max(0, (qty * rate) - discount);
        const tax = taxRate > 0 ? base * taxRate / 100 : 0;
        const total = base + tax;
        tr.querySelector('.tax-amount').value = tax.toFixed(2);
        tr.querySelector('.line-total').value = total.toFixed(2);
        recalcTotals();
    }

    function recalcTotals() {
        let baseSum = 0, taxSum = 0;
        tableBody.querySelectorAll('tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty')?.value) || 0;
            const rate = parseFloat(tr.querySelector('.rate')?.value) || 0;
            const discount = parseFloat(tr.querySelector('.discount')?.value) || 0;
            const base = Math.max(0, (qty * rate) - discount);
            const tax = parseFloat(tr.querySelector('.tax-amount')?.value) || 0;
            baseSum += base;
            taxSum += tax;
        });
        const preGrand = baseSum + taxSum;
        const dtype = discountType?.value || '';
        const dval = parseFloat(discountValue?.value) || 0;
        const dmode = discountMode?.value || 'percent';
        const discountBase = dtype === 'before_tax' ? baseSum : preGrand;
        let dAmount = 0;
        if (dtype && dval > 0 && discountBase > 0) {
            dAmount = dmode === 'percent' ? (discountBase * (dval / 100)) : dval;
            dAmount = Math.min(dAmount, discountBase);
        }

        const grandBase = Math.max(0, preGrand - dAmount);
        // When penalty is already applied, server-side totals/balance already include it.
        // The UI JS recalc ignores penalty, so we add it back for a consistent display.
        const grand = penaltyAlreadyApplied ? (grandBase + penaltyAppliedAmount) : grandBase;
        subTotalDisplay.value = baseSum.toFixed(2);
        taxTotalDisplay.value = taxSum.toFixed(2);
        discountAmountDisplay.value = dAmount.toFixed(2);
        discountAmount.value = dAmount.toFixed(2);
        grandTotalDisplay.value = grand.toFixed(2);
        totalField.value = grand.toFixed(2);
        balanceField.value = grand.toFixed(2);
    }

    function ensureDiscountTypeIfValue() {
        const dtype = discountType?.value || '';
        const dval = parseFloat(discountValue?.value) || 0;
        if (!dtype && dval > 0) {
            alert('Please select Discount Type before entering a discount.');
            discountValue.value = 0;
            discountValue.focus();
            return false;
        }
        return true;
    }

    function bindRow(tr) {
        ['qty','rate','discount','tax-rate'].forEach(cls => {
            tr.querySelectorAll('.' + cls).forEach(input => {
                input.addEventListener('input', () => recalcRow(tr));
            });
        });
        tr.querySelectorAll('.tax-id').forEach(select => {
            select.addEventListener('change', () => {
                const rate = parseFloat(select.selectedOptions[0]?.dataset.rate || 0);
                tr.querySelector('.tax-rate').value = rate;
                recalcRow(tr);
            });
        });
        tr.querySelector('.remove-row').addEventListener('click', () => {
            if (tableBody.rows.length > 1) tr.remove();
            recalcTotals();
        });
    }

    tableBody.querySelectorAll('tr').forEach(bindRow);
    tableBody.querySelectorAll('tr').forEach(recalcRow);

    discountType?.addEventListener('change', recalcTotals);
    discountMode?.addEventListener('change', recalcTotals);
    discountValue?.addEventListener('input', () => {
        if (ensureDiscountTypeIfValue()) recalcTotals();
    });

    addBtn.addEventListener('click', () => {
        const index = tableBody.rows.length;
        const tpl = `
        <tr>
            <td>
                <input type="text" name="items[${index}][item_name]" class="form-control item-name" required>
                <input type="hidden" name="items[${index}][discount]" class="discount" value="0">
            </td>
            <td><input type="text" name="items[${index}][description]" class="form-control"></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][rate]" class="form-control rate text-end" value="0"></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][quantity]" class="form-control qty text-end" value="1"></td>
            <td>
                <select name="items[${index}][tax_id]" class="form-select tax-id">
                    <option value="">None</option>
                    @foreach($taxes as $id => $name)
                        <option value="{{ $id }}" data-rate="{{ $taxRatesMap[$id] ?? 0 }}">{{ $name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][tax_rate]" class="form-control tax-rate text-end" value="0"></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][tax_amount]" class="form-control tax-amount text-end" value="0" readonly></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][line_total]" class="form-control line-total text-end" value="0" readonly></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
            </td>
        </tr>`;
        const temp = document.createElement('tbody');
        temp.innerHTML = tpl.trim();
        const tr = temp.firstElementChild;
        tableBody.appendChild(tr);
        bindRow(tr);
        recalcRow(tr);
    });

    function hydrateHeaderPreview(data) {
        if (!invoiceHeaderPreview || !data) return;
        if (headerName) headerName.textContent = data.header_name || '';
        if (headerReference) headerReference.textContent = data.unique_reference_number || '';
        if (headerDescription) headerDescription.textContent = data.header_description || '-';
        invoiceHeaderPreview.classList.remove('d-none');
    }

    function clearHeaderPreview() {
        if (!invoiceHeaderPreview) return;
        if (headerName) headerName.textContent = '';
        if (headerReference) headerReference.textContent = '';
        if (headerDescription) headerDescription.textContent = '-';
        invoiceHeaderPreview.classList.add('d-none');
    }

    // Penalty / late payment UI (show/hide + enable/disable)
    const penaltyEnabledCheckbox = document.getElementById('penalty-enabled');
    const penaltyConfigWrapper = document.getElementById('penalty-config-wrapper');
    const penaltyExtraFields = document.getElementById('penalty-extra-fields');
    const penaltyTypeSelect = document.getElementById('penalty-type');
    const penaltyFixedRateInput = document.getElementById('penalty-fixed-rate');
    const penaltyGraceDaysInput = document.getElementById('penalty-grace-days');
    const penaltyMaxAmountInput = document.getElementById('penalty-max-amount');
    const penaltyGlAccountSelect = document.getElementById('penalty-gl-account-id');

    function setPenaltyInputsDisabled(disabled) {
        [penaltyTypeSelect, penaltyFixedRateInput, penaltyGraceDaysInput, penaltyMaxAmountInput, penaltyGlAccountSelect]
            .forEach(el => {
                if (!el) return;
                el.disabled = !!disabled;
            });
    }

    function syncPenaltyUI() {
        if (!penaltyEnabledCheckbox) return;
        const locked = !!penaltyEnabledCheckbox.disabled; // if penalty already applied
        const enabled = !!penaltyEnabledCheckbox.checked;

        const showExtra = locked || enabled;
        if (penaltyExtraFields) penaltyExtraFields.classList.toggle('d-none', !showExtra);

        // When locked (already applied), keep all fields disabled.
        setPenaltyInputsDisabled(locked || !enabled);
    }

    penaltyEnabledCheckbox?.addEventListener('change', syncPenaltyUI);
    syncPenaltyUI();
});
</script>
@endpush
