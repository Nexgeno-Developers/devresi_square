@php
    $isEdit = $plan->exists;
@endphp

<form method="POST" action="{{ $isEdit ? route('backend.saas.plans.update', $plan) : route('backend.saas.plans.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $plan->code) }}" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="target_account_type" class="form-label">Target account type</label>
            <select name="target_account_type" id="target_account_type" class="form-select @error('target_account_type') is-invalid @enderror" required>
                <option value="">Select type</option>
                @foreach($targetAccountTypes as $type)
                    <option value="{{ $type }}" @selected(old('target_account_type', $plan->target_account_type) === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
            @error('target_account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="monthly_price" class="form-label">Monthly price</label>
            <div class="input-group">
                <span class="input-group-text">&pound;</span>
                <input type="number" step="0.01" min="0" name="monthly_price" id="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" value="{{ old('monthly_price', $isEdit ? $plan->monthlyPriceMajor() : '0.00') }}" required>
                @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label for="annual_price" class="form-label">Annual price</label>
            <div class="input-group">
                <span class="input-group-text">&pound;</span>
                <input type="number" step="0.01" min="0" name="annual_price" id="annual_price" class="form-control @error('annual_price') is-invalid @enderror" value="{{ old('annual_price', $isEdit ? $plan->annualPriceMajor() : '0.00') }}" required>
                @error('annual_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label for="currency" class="form-label">Currency</label>
            <input type="text" name="currency" id="currency" maxlength="3" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $plan->currency ?: 'GBP') }}" required>
            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="stripe_monthly_price_id" class="form-label">Stripe monthly price ID <span class="text-muted">(optional)</span></label>
            <input type="text" name="stripe_monthly_price_id" id="stripe_monthly_price_id" class="form-control @error('stripe_monthly_price_id') is-invalid @enderror" value="{{ old('stripe_monthly_price_id', $plan->stripe_monthly_price_id) }}">
            @error('stripe_monthly_price_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="stripe_annual_price_id" class="form-label">Stripe annual price ID <span class="text-muted">(optional)</span></label>
            <input type="text" name="stripe_annual_price_id" id="stripe_annual_price_id" class="form-control @error('stripe_annual_price_id') is-invalid @enderror" value="{{ old('stripe_annual_price_id', $plan->stripe_annual_price_id) }}">
            @error('stripe_annual_price_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-2 mb-3">
            <label for="trial_days" class="form-label">Trial days</label>
            <input type="number" min="0" name="trial_days" id="trial_days" class="form-control @error('trial_days') is-invalid @enderror" value="{{ old('trial_days', $plan->trial_days ?? 0) }}" required>
            @error('trial_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label for="property_limit" class="form-label">Property limit</label>
            <input type="number" min="0" name="property_limit" id="property_limit" class="form-control @error('property_limit') is-invalid @enderror" value="{{ old('property_limit', $plan->property_limit ?? 0) }}" required>
            @error('property_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label for="branch_limit" class="form-label">Branch limit</label>
            <input type="number" min="0" name="branch_limit" id="branch_limit" class="form-control @error('branch_limit') is-invalid @enderror" value="{{ old('branch_limit', $plan->branch_limit ?? 0) }}" required>
            @error('branch_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label for="staff_limit" class="form-label">Staff limit</label>
            <input type="number" min="0" name="staff_limit" id="staff_limit" class="form-control @error('staff_limit') is-invalid @enderror" value="{{ old('staff_limit', $plan->staff_limit ?? 0) }}" required>
            @error('staff_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label for="property_manager_limit" class="form-label">PM limit</label>
            <input type="number" min="0" name="property_manager_limit" id="property_manager_limit" class="form-control @error('property_manager_limit') is-invalid @enderror" value="{{ old('property_manager_limit', $plan->property_manager_limit ?? 0) }}" required>
            @error('property_manager_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label for="sort_order" class="form-label">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" required>
            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-4">
        @foreach([
            'allow_company_profile' => 'Company profile',
            'allow_invoice_branding' => 'Invoice branding',
            'allow_roles_permissions' => 'Roles and permissions',
            'allow_contact_login' => 'Contact login',
            'is_active' => 'Active',
        ] as $field => $label)
            <div class="col-md-3 mb-2">
                <input type="hidden" name="{{ $field }}" value="0">
                <div class="form-check">
                    <input type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1" class="form-check-input" @checked((bool) old($field, $plan->{$field}))>
                    <label for="{{ $field }}" class="form-check-label">{{ $label }}</label>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Plan' : 'Create Plan' }}</button>
        <a href="{{ route('backend.saas.plans.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
