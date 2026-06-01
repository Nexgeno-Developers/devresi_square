@if($errors->any())
<div class="alert alert-danger mb-3">
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Basic Info</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="planName"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $plan->name ?? '') }}"
                       placeholder="e.g. Starter, Pro, Enterprise" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Sort Order</label>
                <input type="number" name="sort_order"
                       class="form-control"
                       value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="2"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Short description shown on the plan card…">{{ old('description', $plan->description ?? '') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Badge Label</label>
                <input type="text" name="badge_label"
                       class="form-control"
                       value="{{ old('badge_label', $plan->badge_label ?? '') }}"
                       placeholder="e.g. Most Popular, Best Value">
                <small class="text-muted">Shown as a highlight badge on the plan card.</small>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured"
                           value="1" {{ old('is_featured', $plan->is_featured ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="isFeatured">Featured Plan</label>
                    <div><small class="text-muted">Highlighted with a border</small></div>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                           value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="isActive">Active</label>
                    <div><small class="text-muted">Visible to users</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Pricing</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Monthly Price (£) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">£</span>
                    <input type="number" name="price_monthly" id="priceMonthly" step="0.01" min="0"
                           class="form-control @error('price_monthly') is-invalid @enderror"
                           value="{{ old('price_monthly', $plan->price_monthly ?? '') }}"
                           placeholder="0.00" required>
                    @error('price_monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Yearly Price (£) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">£</span>
                    <input type="number" name="price_yearly" id="priceYearly" step="0.01" min="0"
                           class="form-control @error('price_yearly') is-invalid @enderror"
                           value="{{ old('price_yearly', $plan->price_yearly ?? '') }}"
                           placeholder="0.00" required>
                    @error('price_yearly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <small class="text-muted" id="savingHint"></small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Limits <small class="text-muted fw-normal">(leave blank for unlimited)</small></h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Max Properties</label>
                <input type="number" name="max_properties" min="1"
                       class="form-control"
                       value="{{ old('max_properties', $plan->max_properties ?? '') }}"
                       placeholder="Unlimited">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Max Staff</label>
                <input type="number" name="max_staff" min="1"
                       class="form-control"
                       value="{{ old('max_staff', $plan->max_staff ?? '') }}"
                       placeholder="Unlimited">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Max Tenancies</label>
                <input type="number" name="max_tenancies" min="1"
                       class="form-control"
                       value="{{ old('max_tenancies', $plan->max_tenancies ?? '') }}"
                       placeholder="Unlimited">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Features</h6></div>
    <div class="card-body">
        <label class="form-label fw-semibold">Feature List</label>
        <div id="featuresWrapper">
            @php
                $existingFeatures = isset($plan) ? ($plan->features ?? []) : (old('features') ? (is_array(old('features')) ? old('features') : []) : []);
                if (empty($existingFeatures)) $existingFeatures = [''];
            @endphp
            @foreach($existingFeatures as $feature)
            <div class="input-group mb-2 feature-row">
                <span class="input-group-text bg-success text-white"><i class="fas fa-check"></i></span>
                <input type="text" name="features[]" class="form-control"
                       placeholder="e.g. Priority support" value="{{ $feature }}">
                <button type="button" class="btn btn-outline-danger remove-feature" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endforeach
        </div>
        <button type="button" id="addFeature" class="btn btn-sm btn-outline-primary mt-1">
            <i class="fas fa-plus me-1"></i> Add Feature
        </button>
        <div><small class="text-muted">Each entry becomes a ✓ bullet on the plan card.</small></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('addFeature').addEventListener('click', function () {
        const wrapper = document.getElementById('featuresWrapper');
        const row = document.createElement('div');
        row.className = 'input-group mb-2 feature-row';
        row.innerHTML = `
            <span class="input-group-text bg-success text-white"><i class="fas fa-check"></i></span>
            <input type="text" name="features[]" class="form-control" placeholder="e.g. Priority support">
            <button type="button" class="btn btn-outline-danger remove-feature" title="Remove">
                <i class="fas fa-times"></i>
            </button>`;
        wrapper.appendChild(row);
        row.querySelector('input').focus();
        updatePreviewFeatures();
    });

    document.getElementById('featuresWrapper').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-feature');
        if (!btn) return;
        const rows = document.querySelectorAll('.feature-row');
        if (rows.length > 1) {
            btn.closest('.feature-row').remove();
        } else {
            btn.closest('.feature-row').querySelector('input').value = '';
        }
        updatePreviewFeatures();
    });

    document.getElementById('featuresWrapper').addEventListener('input', function (e) {
        if (e.target.matches('input[name="features[]"]')) updatePreviewFeatures();
    });

    function updatePreviewFeatures() {
        const featList = document.getElementById('previewFeatures');
        if (!featList) return;
        featList.innerHTML = Array.from(document.querySelectorAll('input[name="features[]"]'))
            .map(i => i.value.trim()).filter(v => v)
            .map(v => `<li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>${v}</li>`)
            .join('');
    }

    const monthly = document.getElementById('priceMonthly');
    const yearly  = document.getElementById('priceYearly');
    const hint    = document.getElementById('savingHint');

    function updateHint() {
        const m = parseFloat(monthly?.value);
        const y = parseFloat(yearly?.value);
        if (m > 0 && y > 0) {
            const saving = Math.round((1 - (y / (m * 12))) * 100);
            if (hint) hint.textContent = saving > 0 ? 'Saves ' + saving + '% vs monthly' : '';
        } else {
            if (hint) hint.textContent = '';
        }
    }

    monthly?.addEventListener('input', updateHint);
    yearly?.addEventListener('input', updateHint);
    updateHint();
});
</script>
@endpush
