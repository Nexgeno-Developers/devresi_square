@php
    $isEdit = $addon->exists;
@endphp

<form method="POST" action="{{ $isEdit ? route('backend.saas.addons.update', $addon) : route('backend.saas.addons.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $addon->code) }}" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $addon->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="addon_type" class="form-label">Addon type</label>
            <select name="addon_type" id="addon_type" class="form-select @error('addon_type') is-invalid @enderror" required>
                <option value="">Select type</option>
                @foreach($addonTypes as $type)
                    <option value="{{ $type }}" @selected(old('addon_type', $addon->addon_type) === $type)>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
            @error('addon_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="monthly_price" class="form-label">Monthly price</label>
            <div class="input-group">
                <span class="input-group-text">&pound;</span>
                <input type="number" step="0.01" min="0" name="monthly_price" id="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" value="{{ old('monthly_price', $isEdit ? $addon->monthlyPriceMajor() : '0.00') }}" required>
                @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label for="annual_price" class="form-label">Annual price</label>
            <div class="input-group">
                <span class="input-group-text">&pound;</span>
                <input type="number" step="0.01" min="0" name="annual_price" id="annual_price" class="form-control @error('annual_price') is-invalid @enderror" value="{{ old('annual_price', $isEdit ? $addon->annualPriceMajor() : '0.00') }}" required>
                @error('annual_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label for="currency" class="form-label">Currency</label>
            <input type="text" name="currency" id="currency" maxlength="3" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $addon->currency ?: 'GBP') }}" required>
            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="stripe_monthly_price_id" class="form-label">Stripe monthly price ID</label>
            <input type="text" name="stripe_monthly_price_id" id="stripe_monthly_price_id" class="form-control @error('stripe_monthly_price_id') is-invalid @enderror" value="{{ old('stripe_monthly_price_id', $addon->stripe_monthly_price_id) }}">
            @error('stripe_monthly_price_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="stripe_annual_price_id" class="form-label">Stripe annual price ID</label>
            <input type="text" name="stripe_annual_price_id" id="stripe_annual_price_id" class="form-control @error('stripe_annual_price_id') is-invalid @enderror" value="{{ old('stripe_annual_price_id', $addon->stripe_annual_price_id) }}">
            @error('stripe_annual_price_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="grant_quantity" class="form-label">Grant quantity</label>
            <input type="number" min="1" name="grant_quantity" id="grant_quantity" class="form-control @error('grant_quantity') is-invalid @enderror" value="{{ old('grant_quantity', $addon->grant_quantity ?? 1) }}" required>
            @error('grant_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <input type="hidden" name="is_stackable" value="0">
            <div class="form-check">
                <input type="checkbox" name="is_stackable" id="is_stackable" value="1" class="form-check-input" @checked((bool) old('is_stackable', $addon->is_stackable))>
                <label for="is_stackable" class="form-check-label">Stackable</label>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" @checked((bool) old('is_active', $addon->is_active))>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Addon' : 'Create Addon' }}</button>
        <a href="{{ route('backend.saas.addons.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
