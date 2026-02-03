@csrf

<div class="row">
    <!-- Name -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $accountHeader->name ?? '') }}" required>
    </div>

    <!-- Charge On -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Charge On <span class="text-danger">*</span></label>
        <select name="charge_on" class="form-select" required>
            <option value="">-- Select --</option>
            @foreach(['property','tenancy','landlord','contractor','applicants','all'] as $option)
                <option value="{{ $option }}" {{ old('charge_on', $accountHeader->charge_on ?? '') == $option ? 'selected' : '' }}>
                    {{ ucfirst($option) }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Description -->
    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $accountHeader->description ?? '') }}</textarea>
    </div>
</div>

<hr>

<div class="row">
    <!-- Who Can View -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Who Can View</label>
        <select name="who_can_view" class="form-select">
            @foreach(['tenant','owner','contractor','everyone'] as $option)
                <option value="{{ $option }}" {{ old('who_can_view', $accountHeader->who_can_view ?? 'everyone') == $option ? 'selected' : '' }}>
                    {{ ucfirst($option) }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Charge In -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Charge In</label>
        <select name="charge_in" class="form-select">
            @foreach(['arrears','advance','anytime'] as $option)
                <option value="{{ $option }}" {{ old('charge_in', $accountHeader->charge_in ?? 'anytime') == $option ? 'selected' : '' }}>
                    {{ ucfirst($option) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<hr>

<div class="row">
    <!-- Settle Through -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Settle Through</label>
        <select name="settle_through" class="form-select">
            @foreach(['credit_note','debit_note','refund','all'] as $option)
                <option value="{{ $option }}" {{ old('settle_through', $accountHeader->settle_through ?? 'all') == $option ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_',' ',$option)) }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Transaction Between -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Transaction Between</label>
        <select name="transaction_between" class="form-select">
            @foreach(['tenant_landlord','landlord_agent','agent_contractor','internal_staff_agent','landlord_contractor','landlord_management','all'] as $option)
                <option value="{{ $option }}" {{ old('transaction_between', $accountHeader->transaction_between ?? 'all') == $option ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_',' ',$option)) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<hr>

<div class="row">
    <!-- Penalty Type -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Penalty Type</label>
        <select name="penalty_type" class="form-select">
            <option value="">-- None --</option>
            @foreach(['percentage','flat_rate'] as $option)
                <option value="{{ $option }}" {{ old('penalty_type', $accountHeader->penalty_type ?? '') == $option ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ',$option)) }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Tax Type -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Tax Type</label>
        <select name="tax_type" class="form-select">
            <option value="">-- None --</option>
            @foreach(['percentage','flat_rate'] as $option)
                <option value="{{ $option }}" {{ old('tax_type', $accountHeader->tax_type ?? '') == $option ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ',$option)) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<hr>

<div class="row">
    <!-- Boolean Toggles -->
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="reminders" value="1" class="form-check-input" {{ old('reminders', $accountHeader->reminders ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Reminders</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="agent_fees" value="1" class="form-check-input" {{ old('agent_fees', $accountHeader->agent_fees ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Agent Fees</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="require_bank_details" value="1" class="form-check-input" {{ old('require_bank_details', $accountHeader->require_bank_details ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Bank Details Required</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="can_have_duration" value="1" class="form-check-input" {{ old('can_have_duration', $accountHeader->can_have_duration ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Has Duration</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="duration_parameter_required" value="1" class="form-check-input" {{ old('duration_parameter_required', $accountHeader->duration_parameter_required ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Duration Param Required</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="tax_included" value="1" class="form-check-input" {{ old('tax_included', $accountHeader->tax_included ?? false) ? 'checked' : '' }}>
        <label class="form-check-label">Tax Included</label>
    </div>
    <div class="col-md-3 mb-3 form-check">
        <input type="checkbox" name="active" value="1" class="form-check-input" {{ old('active', $accountHeader->active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label">Active</label>
    </div>
</div>

<hr>

<div class="text-end">
    <button type="submit" class="btn btn-success">{{ $buttonText ?? 'Save' }}</button>
</div>
