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
            <div class="col-md-12">
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

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Reminder Days Before Due</label>
                <input
                    type="number"
                    min="0"
                    max="365"
                    name="reminder_days_before_due"
                    id="reminder-days-before-due"
                    class="form-control"
                    value="{{ $oldVal('reminder_days_before_due') }}"
                >
                <small class="text-muted">Override reminder timing for this invoice/series.</small>
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
                <div id="property-context-box" class="alert alert-info border mb-0 d-none stop-propagation">
                    <div class="fw-semibold mb-2">Property Context</div>
                    <div><strong>Property:</strong> <span data-property-name>-</span></div>
                    <div><strong>Address:</strong> <span data-property-address>-</span></div>
                    <div><strong>Owner:</strong> <span data-property-owners>-</span></div>
                    <div><strong>Tenant:</strong> <span data-property-tenants>-</span></div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div id="tenancy-context-box" class="alert alert-warning border mb-0 d-none stop-propagation">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="fw-semibold">Tenancy Context</div>
                        <button type="button" class="btn btn-sm btn-outline-dark d-none" id="tenancy-view-btn">View Tenancy</button>
                    </div>

                    <div class="mt-2">
                        <label class="form-label mb-1">Suggested Tenancy</label>
                        <select id="tenancy-suggest-select" class="form-select form-select-sm"></select>
                    </div>

                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="tenancy-apply-rent-btn">Apply Rent Amount</button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="tenancy-enable-recurring-btn">Enable Recurring from Tenancy</button>
                        <span class="text-muted align-self-center" style="font-size: 12px;" data-tenancy-recurrence-hint></span>
                    </div>

                    <div id="tenancy-suggest-banner" class="mt-2 d-none">
                        <div class="alert alert-light border mb-0 p-2">
                            Suggested: Link To = <strong>Tenancy #<span data-suggest-tenancy-id>-</span></strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-primary" id="tenancy-apply-btn">Apply</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="tenancy-keep-property-btn">Keep Property</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2" style="font-size: 13px;">
                        <div><strong>Move In:</strong> <span data-tenancy-move-in>-</span></div>
                        <div><strong>Move Out:</strong> <span data-tenancy-move-out>-</span></div>
                        <div><strong>Rent:</strong> <span data-tenancy-rent>-</span></div>
                        <div><strong>Frequency:</strong> <span data-tenancy-frequency>-</span></div>
                        <div><strong>Main Tenant:</strong> <span data-tenancy-main-tenant>-</span></div>
                        <div class="text-muted mt-1" style="font-size: 12px;" data-tenancy-note></div>
                    </div>
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
    const tenancyContextUrl = (propertyId, tenantId) => "{{ route('backend.accounting.sale.invoices.tenancyContext', ['property' => '___PID___', 'tenant' => '___TID___']) }}"
        .replace('___PID___', propertyId)
        .replace('___TID___', tenantId);
    const tenancyShowUrl = (id) => "{{ route('admin.tenancies.show', ['id' => '___ID___']) }}".replace('___ID___', id);
    const linkToTypeInput = document.getElementById('link-to-type');
    const linkToIdInput = document.getElementById('link-to-id');
    const chargeToTypeInput = document.getElementById('charge-to-type');
    const chargeToIdInput = document.getElementById('charge-to-id');
    const linkToPropertySelect = document.getElementById('link-to-property-select');
    const linkToTenancySelect = document.getElementById('link-to-tenancy-select');
    const chargeToOwnerSelect = document.getElementById('charge-to-owner-select');
    const chargeToTenantSelect = document.getElementById('charge-to-tenant-select');
    const propertyContextBox = document.getElementById('property-context-box');
    const propertyNameNode = propertyContextBox?.querySelector('[data-property-name]');
    const propertyAddressNode = propertyContextBox?.querySelector('[data-property-address]');
    const propertyOwnersNode = propertyContextBox?.querySelector('[data-property-owners]');
    const propertyTenantsNode = propertyContextBox?.querySelector('[data-property-tenants]');

    const tenancyContextBox = document.getElementById('tenancy-context-box');
    const tenancySuggestSelect = document.getElementById('tenancy-suggest-select');
    const tenancyViewBtn = document.getElementById('tenancy-view-btn');
    const tenancySuggestBanner = document.getElementById('tenancy-suggest-banner');
    const tenancySuggestTenancyIdNode = tenancyContextBox?.querySelector('[data-suggest-tenancy-id]');
    const tenancyMoveInNode = tenancyContextBox?.querySelector('[data-tenancy-move-in]');
    const tenancyMoveOutNode = tenancyContextBox?.querySelector('[data-tenancy-move-out]');
    const tenancyRentNode = tenancyContextBox?.querySelector('[data-tenancy-rent]');
    const tenancyFrequencyNode = tenancyContextBox?.querySelector('[data-tenancy-frequency]');
    const tenancyMainTenantNode = tenancyContextBox?.querySelector('[data-tenancy-main-tenant]');
    const tenancyNoteNode = tenancyContextBox?.querySelector('[data-tenancy-note]');
    const tenancyRecurrenceHintNode = tenancyContextBox?.querySelector('[data-tenancy-recurrence-hint]');
    const tenancyApplyBtn = document.getElementById('tenancy-apply-btn');
    const tenancyKeepPropertyBtn = document.getElementById('tenancy-keep-property-btn');
    const tenancyApplyRentBtn = document.getElementById('tenancy-apply-rent-btn');
    const tenancyEnableRecurringBtn = document.getElementById('tenancy-enable-recurring-btn');

    function keepContextClickInside(event) {
        event.stopPropagation();
    }

    // Prevent global document-click handlers (theme helpers) from hiding these boxes.
    [propertyContextBox, tenancyContextBox].forEach(box => {
        if (!box) return;

        ['pointerdown', 'mousedown', 'mouseup', 'touchstart', 'touchend', 'click'].forEach(eventName => {
            box.addEventListener(eventName, keepContextClickInside);
        });

        box.querySelectorAll('button, select, input, textarea, a').forEach(control => {
            ['pointerdown', 'mousedown', 'mouseup', 'touchstart', 'touchend', 'click'].forEach(eventName => {
                control.addEventListener(eventName, keepContextClickInside);
            });
        });
    });

    const rentIssueDaysBeforeDue = @json((int) get_setting('rent_invoice_issue_days_before_due', 7));
    const moneySymbol = @json(getPoundSymbol());
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
    const repeatEveryCustomInput = document.getElementById('repeat-every-custom');
    const repeatTypeCustomSelect = document.getElementById('repeat-type-custom');

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

    let invoiceDateDirty = false;
    let dueDateDirty = false;
    let recurringDirty = false;
    let suppressDirty = false;
    let tenancySuggestionDismissed = false;
    let rentAutoApplied = false;
    let lastTenancyDriverKey = null;
    let currentTenancyRow = null;

    function markDirty(kind) {
        if (suppressDirty) return;
        if (kind === 'invoice_date') invoiceDateDirty = true;
        if (kind === 'due_date') dueDateDirty = true;
        if (kind === 'recurring') recurringDirty = true;
    }

    invoiceDateInput?.addEventListener('input', () => markDirty('invoice_date'));
    invoiceDateInput?.addEventListener('change', () => markDirty('invoice_date'));
    dueDateInput?.addEventListener('input', () => markDirty('due_date'));
    dueDateInput?.addEventListener('change', () => markDirty('due_date'));

    [recurringSelect, repeatEveryCustomInput, repeatTypeCustomSelect, recurringCyclesInput, unlimitedCyclesCheckbox]
        .forEach(el => el?.addEventListener('change', () => markDirty('recurring')));

    function isRentMode() {
        const name = (headerName?.textContent || '').trim().toLowerCase();
        if (!name) return false;
        return /\brent\b/.test(name);
    }

    function parseYmd(ymd) {
        if (!ymd) return null;
        const parts = String(ymd).split('-').map(n => parseInt(n, 10));
        if (parts.length !== 3 || parts.some(n => !Number.isFinite(n))) return null;
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function toYmd(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function startOfDay(date) {
        const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function safeMonthlyDate(baseDate, monthsToAdd, targetDay) {
        const b = startOfDay(baseDate);
        const baseYear = b.getFullYear();
        const baseMonthIndex = b.getMonth(); // 0..11
        const totalMonths = (baseYear * 12) + baseMonthIndex + Math.max(1, monthsToAdd);

        const year = Math.floor(totalMonths / 12);
        const monthIndex = totalMonths % 12;
        const lastDay = new Date(year, monthIndex + 1, 0).getDate();
        const day = Math.min(Math.max(1, targetDay), lastDay);

        return new Date(year, monthIndex, day);
    }

    function setDueDate() {
        if (!invoiceDateInput.value) return;
        if (rentAutoApplied) return;
        if (invoiceDateDirty || dueDateDirty) return;
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
            maybeLoadTenancyContext();

            try {
                const res = await fetch(headerJsonUrl(selected.id));
                if (res.ok) {
                    const full = await res.json();
                    hydrateHeaderPreview(full);
                    maybeLoadTenancyContext();
                }
            } catch (err) {
                console.warn('Invoice header fetch failed', err);
            }
        });

        invoiceHeaderSelect.on('select2:clear', () => {
            clearHeaderPreview();
            maybeLoadTenancyContext();
        });
    }

    function setupEntitySelector(groupName, typeInput, hiddenIdInput) {
        const wrappers = Array.from(document.querySelectorAll(`[data-entity-group="${groupName}"]`));
        let previousType = null;

        function syncVisibleSelect(force = false) {
            const currentType = typeInput?.value || '';
            if (!force && previousType === currentType) {
                return;
            }
            previousType = currentType;

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
            const activeValue = activeSelect?.value || '';
            if (activeValue) {
                hiddenIdInput.value = activeValue;
            } else if (!force) {
                hiddenIdInput.value = '';
            }
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

        typeInput?.addEventListener('change', () => syncVisibleSelect());
        syncVisibleSelect(true);
    }

    setupEntitySelector('link_to', linkToTypeInput, linkToIdInput);
    setupEntitySelector('charge_to', chargeToTypeInput, chargeToIdInput);

    let previousContextValues = {
        linkToType: linkToTypeInput?.value || '',
        linkToProperty: linkToPropertySelect?.value || '',
        linkToTenancy: linkToTenancySelect?.value || '',
        chargeToType: chargeToTypeInput?.value || '',
        chargeToTenant: chargeToTenantSelect?.value || '',
    };

    function hasContextDropdownChanged(key, value) {
        const normalizedValue = value || '';
        if (previousContextValues[key] === normalizedValue) {
            return false;
        }

        previousContextValues[key] = normalizedValue;
        return true;
    }

    function clearTenancyContext() {
        if (tenancyContextBox) tenancyContextBox.classList.add('d-none');
        if (tenancySuggestSelect) tenancySuggestSelect.innerHTML = '';
        if (tenancyViewBtn) tenancyViewBtn.classList.add('d-none');
        if (tenancySuggestBanner) tenancySuggestBanner.classList.add('d-none');
        if (tenancySuggestTenancyIdNode) tenancySuggestTenancyIdNode.textContent = '-';
        if (tenancyMoveInNode) tenancyMoveInNode.textContent = '-';
        if (tenancyMoveOutNode) tenancyMoveOutNode.textContent = '-';
        if (tenancyRentNode) tenancyRentNode.textContent = '-';
        if (tenancyFrequencyNode) tenancyFrequencyNode.textContent = '-';
        if (tenancyMainTenantNode) tenancyMainTenantNode.textContent = '-';
        if (tenancyRecurrenceHintNode) tenancyRecurrenceHintNode.textContent = '';
        if (tenancyNoteNode) tenancyNoteNode.textContent = '';
    }

    function setTenancySuggestOptions(tenancies, selectedId) {
        if (!tenancySuggestSelect) return;

        const options = [];
        (tenancies || []).forEach(t => {
            const mi = t.move_in ? String(t.move_in) : '';
            const mo = t.move_out ? String(t.move_out) : '';
            const dateLabel = mi ? (mo ? `${mi} → ${mo}` : `${mi} →`) : '';
            const label = `Tenancy #${t.id}${dateLabel ? ' (' + dateLabel + ')' : ''}`;
            const selected = String(selectedId) === String(t.id) ? ' selected' : '';
            options.push(`<option value="${t.id}"${selected}>${label}</option>`);
        });

        tenancySuggestSelect.innerHTML = options.join('');
    }

    function renderTenancyRow(row) {
        if (!row || !tenancyContextBox) {
            clearTenancyContext();
            return;
        }

        const freq = getTenancyFrequency(row);
        currentTenancyRow = row;
        tenancyContextBox.classList.remove('d-none');
        if (tenancySuggestTenancyIdNode) tenancySuggestTenancyIdNode.textContent = row.id ?? '-';
        if (tenancyMoveInNode) tenancyMoveInNode.textContent = row.move_in || '-';
        if (tenancyMoveOutNode) tenancyMoveOutNode.textContent = row.move_out || '-';
        if (tenancyRentNode) {
            if (row.rent !== null && row.rent !== '' && !isNaN(Number(row.rent))) {
                tenancyRentNode.textContent = `${moneySymbol}${Number(row.rent).toFixed(2)}`;
            } else {
                tenancyRentNode.textContent = '-';
            }
        }
        if (tenancyFrequencyNode) tenancyFrequencyNode.textContent = row.frequency || 'Monthly';
        if (tenancyMainTenantNode) {
            const mt = row.main_tenant;
            tenancyMainTenantNode.textContent = mt ? `${mt.name || ''}${mt.email ? ' (' + mt.email + ')' : ''}`.trim() : '-';
        }

        if (tenancyRecurrenceHintNode) {
            const hint = freq === 'monthly'
                ? 'Suggested recurrence: Monthly'
                : (freq === 'weekly' ? 'Suggested recurrence: Weekly' : '');
            tenancyRecurrenceHintNode.textContent = hint;
        }

        if (tenancyApplyRentBtn) {
            tenancyApplyRentBtn.onclick = () => applyRentAmountFromTenancy(row);
        }
        if (tenancyEnableRecurringBtn) {
            tenancyEnableRecurringBtn.onclick = () => enableRecurringFromTenancy(row);
        }

        if (tenancyViewBtn) {
            tenancyViewBtn.classList.toggle('d-none', !row.id);
            tenancyViewBtn.onclick = () => {
                if (!row.id) return;
                extralargeModal(tenancyShowUrl(row.id), 'Tenancy Details');
            };
        }

        const shouldSuggestLink = !tenancySuggestionDismissed
            && (linkToTypeInput?.value === '' || linkToTypeInput?.value === 'Property')
            && !!row.id;

        if (tenancySuggestBanner) {
            tenancySuggestBanner.classList.toggle('d-none', !shouldSuggestLink);
        }

        if (tenancyApplyBtn) {
            tenancyApplyBtn.onclick = () => {
                if (!row.id || !linkToTypeInput || !linkToTenancySelect) return;
                suppressDirty = true;
                linkToTypeInput.value = 'Tenancy';
                linkToTypeInput.dispatchEvent(new Event('change'));
                linkToTenancySelect.value = String(row.id);
                linkToTenancySelect.dispatchEvent(new Event('change'));
                suppressDirty = false;
                tenancySuggestionDismissed = true;
                tenancySuggestBanner?.classList.add('d-none');
            };
        }

        if (tenancyKeepPropertyBtn) {
            tenancyKeepPropertyBtn.onclick = () => {
                tenancySuggestionDismissed = true;
                tenancySuggestBanner?.classList.add('d-none');
            };
        }
    }

    function getTenancyFrequency(row) {
        const freq = String(row?.frequency || '').toLowerCase();
        return freq === 'weekly' ? 'weekly' : 'monthly';
    }

    function computeNextDueDateFromTenancy(row) {
        const moveIn = parseYmd(row?.move_in);
        if (!moveIn) return null;
        const freq = getTenancyFrequency(row);

        const targetDay = moveIn.getDate();

        if (freq !== 'monthly' && freq !== 'weekly') {
            return null;
        }

        if (freq === 'monthly') {
            return safeMonthlyDate(moveIn, 1, targetDay);
        }

        return startOfDay(new Date(moveIn.getFullYear(), moveIn.getMonth(), moveIn.getDate() + 7));
    }

    function computeCyclesUntilMoveOut(row, startDueDate) {
        const moveOut = parseYmd(row?.move_out);
        if (!moveOut) return null;

        const freq = getTenancyFrequency(row);
        const targetDay = parseYmd(row?.move_in)?.getDate() || startDueDate.getDate();
        const end = startOfDay(moveOut);

        let count = 0;
        let due = startOfDay(startDueDate);
        while (due <= end && count < 500) {
            count++;
            if (freq === 'monthly') {
                due = safeMonthlyDate(due, 1, targetDay);
            } else if (freq === 'weekly') {
                due = startOfDay(new Date(due.getFullYear(), due.getMonth(), due.getDate() + 7));
            } else {
                break;
            }
        }

        return count;
    }

    function computeChildCyclesFromTenancy(row) {
        const moveIn = parseYmd(row?.move_in);
        if (!moveIn) return null;

        const moveOut = parseYmd(row?.move_out);
        if (!moveOut) return null;

        const freq = getTenancyFrequency(row);
        const targetDay = moveIn.getDate();
        const end = startOfDay(moveOut);
        let due = startOfDay(moveIn);
        let totalPeriods = 0;

        while (due <= end && totalPeriods < 500) {
            totalPeriods++;
            if (freq === 'monthly') {
                due = safeMonthlyDate(due, 1, targetDay);
            } else if (freq === 'weekly') {
                due = startOfDay(new Date(due.getFullYear(), due.getMonth(), due.getDate() + 7));
            } else {
                break;
            }
        }

        return Math.max(0, totalPeriods - 1);
    }

    function applyRecurringFromTenancy(row, confirmOverwrite = false) {
        if (!row || !recurringSelect || recurringSelect.disabled) return false;

        const freq = getTenancyFrequency(row);
        const cycles = computeChildCyclesFromTenancy(row);
        if (cycles !== null && cycles < 1) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'Cannot enable recurring: tenancy move-out allows no child invoices.';
            return false;
        }

        const alreadySet = recurringSelect.value && recurringSelect.value !== '0';
        if (alreadySet && confirmOverwrite) {
            const ok = confirm('Recurring is already set. Overwrite it using tenancy schedule?');
            if (!ok) return false;
        }

        suppressDirty = true;

        if (freq === 'monthly') {
            recurringSelect.value = '1';
        } else {
            recurringSelect.value = 'custom';
            if (repeatEveryCustomInput) repeatEveryCustomInput.value = '1';
            if (repeatTypeCustomSelect) repeatTypeCustomSelect.value = 'week';
        }

        if (cycles !== null) {
            if (unlimitedCyclesCheckbox) unlimitedCyclesCheckbox.checked = false;
            if (recurringCyclesInput) recurringCyclesInput.value = String(cycles);
        } else {
            if (unlimitedCyclesCheckbox) unlimitedCyclesCheckbox.checked = true;
            if (recurringCyclesInput) recurringCyclesInput.value = '';
        }

        suppressDirty = false;
        syncRecurringFields();

        return true;
    }

    function applyRentScheduleFromTenancy(row, respectDirty = true) {
        if (!row) return;
        if (!isRentMode()) return;
        if (respectDirty && (invoiceDateDirty || dueDateDirty)) return;

        const due = computeNextDueDateFromTenancy(row);
        if (!due) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'No auto schedule: tenancy move-in missing.';
            return;
        }

        const moveOut = parseYmd(row.move_out);
        if (moveOut && startOfDay(due) > startOfDay(moveOut)) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'No auto schedule: next due date is beyond tenancy move-out.';
            return;
        }

        const dueYmd = toYmd(due);
        const moveIn = parseYmd(row.move_in);
        const invoiceYmd = toYmd(moveIn || due);

        suppressDirty = true;
        if (dueDateInput) dueDateInput.value = dueYmd;
        if (invoiceDateInput) invoiceDateInput.value = invoiceYmd;
        suppressDirty = false;
        rentAutoApplied = true;

        if (!recurringDirty) {
            applyRecurringFromTenancy(row, false);
        }

        if (tenancyNoteNode) tenancyNoteNode.textContent = 'Auto schedule applied from tenancy. Edit dates to override. Use "Enable Recurring from Tenancy" if needed.';
    }

    function applyRentAutoSchedule(row) {
        applyRentScheduleFromTenancy(row, true);
    }

    function applyRentAmountFromTenancy(row) {
        if (!row) return;

        const rent = Number(row.rent);
        if (!Number.isFinite(rent) || rent <= 0) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'Cannot apply rent amount: tenancy rent is missing/invalid.';
            return;
        }

        const body = document.querySelector('#items-table tbody');
        const firstTr = body?.querySelector('tr');
        if (!firstTr) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'Cannot apply rent amount: invoice items table not found.';
            return;
        }

        const nameInput = firstTr.querySelector('.item-name');
        const qtyInput = firstTr.querySelector('.qty');
        const rateInput = firstTr.querySelector('.rate');
        if (!nameInput || !qtyInput || !rateInput) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'Cannot apply rent amount: invoice item inputs not found.';
            return;
        }

        const existingName = String(nameInput.value || '').trim();
        const existingRate = parseFloat(rateInput.value) || 0;
        const existingQty = parseFloat(qtyInput.value) || 0;

        const looksUsed = existingName !== '' || existingRate > 0 || existingQty > 1;
        if (looksUsed) {
            const ok = confirm('Overwrite the first invoice line with tenancy rent amount?');
            if (!ok) return;
        }

        nameInput.value = 'Rent';
        qtyInput.value = '1';
        rateInput.value = rent.toFixed(2);

        try {
            recalcRow(firstTr);
        } catch (e) {
            // ignore
        }

        if (tenancyNoteNode) tenancyNoteNode.textContent = 'Rent amount applied to the first invoice line.';
    }

    function enableRecurringFromTenancy(row) {
        if (!row) return;

        if (!recurringSelect || recurringSelect.disabled) {
            if (tenancyNoteNode) tenancyNoteNode.textContent = 'Cannot enable recurring: recurring UI not found.';
            return;
        }

        applyRentScheduleFromTenancy(row, false);

        const applied = applyRecurringFromTenancy(row, true);
        if (!applied) return;

        recurringDirty = true;
        if (tenancyNoteNode) tenancyNoteNode.textContent = 'Recurring enabled from tenancy. Review cycles/unlimited before saving.';
    }

    async function loadTenancyContext(propertyId, tenantId) {
        if (!propertyId || !tenantId || !isRentMode()) {
            clearTenancyContext();
            return;
        }

        try {
            const response = await fetch(tenancyContextUrl(propertyId, tenantId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error(`Tenancy context request failed with ${response.status}`);
            }

            const payload = await response.json();
            const tenancies = payload?.tenancies || [];
            if (!tenancies.length) {
                clearTenancyContext();
                return;
            }

            const forcedSelectedId = (linkToTypeInput?.value === 'Tenancy' && linkToTenancySelect?.value)
                ? linkToTenancySelect.value
                : null;
            const selectedId = forcedSelectedId || tenancySuggestSelect?.value || payload?.selected_tenancy_id || tenancies[0]?.id;
            setTenancySuggestOptions(tenancies, selectedId);

            const selected = tenancies.find(t => String(t.id) === String(selectedId)) || tenancies[0];
            renderTenancyRow(selected);
            applyRentAutoSchedule(selected);

            if (tenancySuggestSelect) {
                tenancySuggestSelect.onchange = () => {
                    const id = tenancySuggestSelect.value;
                    const row = tenancies.find(t => String(t.id) === String(id)) || tenancies[0];
                    renderTenancyRow(row);
                    applyRentAutoSchedule(row);
                };
            }
        } catch (error) {
            console.warn('Tenancy context fetch failed', error);
            clearTenancyContext();
        }
    }

    function maybeLoadTenancyContext() {
        if (!isRentMode()) {
            lastTenancyDriverKey = null;
            rentAutoApplied = false;
            clearTenancyContext();
            return;
        }

        const propertyId = linkToPropertySelect?.value || '';
        const tenantId = (chargeToTypeInput?.value === 'Tenant') ? (chargeToTenantSelect?.value || '') : '';
        if (!propertyId || !tenantId || (linkToTypeInput?.value !== 'Property' && linkToTypeInput?.value !== 'Tenancy')) {
            lastTenancyDriverKey = null;
            clearTenancyContext();
            return;
        }

        const driverKey = `rent|${propertyId}|${tenantId}`;
        if (driverKey !== lastTenancyDriverKey) {
            tenancySuggestionDismissed = false;
            rentAutoApplied = false;
            lastTenancyDriverKey = driverKey;
        }

        loadTenancyContext(propertyId, tenantId);
    }

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
        // Property context is useful for rent flow even when Link To is switched to Tenancy.
        if (!propertyId || (linkToTypeInput?.value !== 'Property' && linkToTypeInput?.value !== 'Tenancy')) {
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
        if (!hasContextDropdownChanged('linkToType', linkToTypeInput.value)) {
            return;
        }

        if (linkToTypeInput.value === 'Property' && linkToPropertySelect?.value) {
            loadPropertyContext(linkToPropertySelect.value);
            maybeLoadTenancyContext();
            return;
        }

        if ((linkToTypeInput.value === 'Property' || linkToTypeInput.value === 'Tenancy') && linkToPropertySelect?.value) {
            loadPropertyContext(linkToPropertySelect.value);
        } else {
            clearPropertyContext();
        }
        maybeLoadTenancyContext();
    });

    linkToPropertySelect?.addEventListener('change', () => {
        if (!hasContextDropdownChanged('linkToProperty', linkToPropertySelect.value)) {
            return;
        }

        if (linkToTypeInput?.value === 'Property' || linkToTypeInput?.value === 'Tenancy') {
            loadPropertyContext(linkToPropertySelect.value);
            maybeLoadTenancyContext();
        }
    });

    linkToTenancySelect?.addEventListener('change', () => {
        if (!hasContextDropdownChanged('linkToTenancy', linkToTenancySelect.value)) {
            return;
        }

        maybeLoadTenancyContext();
    });

    chargeToTypeInput?.addEventListener('change', () => {
        if (!hasContextDropdownChanged('chargeToType', chargeToTypeInput.value)) {
            return;
        }

        maybeLoadTenancyContext();
    });

    chargeToTenantSelect?.addEventListener('change', () => {
        if (!hasContextDropdownChanged('chargeToTenant', chargeToTenantSelect.value)) {
            return;
        }

        maybeLoadTenancyContext();
    });

    if (linkToTypeInput?.value === 'Property' && linkToPropertySelect?.value) {
        loadPropertyContext(linkToPropertySelect.value);
        maybeLoadTenancyContext();
    } else {
        if ((linkToTypeInput?.value === 'Property' || linkToTypeInput?.value === 'Tenancy') && linkToPropertySelect?.value) {
            loadPropertyContext(linkToPropertySelect.value);
        } else {
            clearPropertyContext();
        }
        maybeLoadTenancyContext();
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

