@csrf
@if(isset($item))
    @method('PUT')
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="header_name" class="form-label">
            Header Name <span class="text-danger">*</span>
        </label>
        <input
            type="text"
            class="form-control"
            id="header_name"
            name="header_name"
            value="{{ old('header_name', $item->header_name ?? '') }}"
            required
        >
    </div>

    <div class="col-md-6 mb-3">
        <label for="unique_reference_number" class="form-label">
            Unique Reference Number
        </label>
        <input
            type="text"
            class="form-control"
            id="unique_reference_number"
            name="unique_reference_number"
            value="{{ old('unique_reference_number', $item->unique_reference_number ?? '') }}"
            placeholder="Leave blank to auto-generate"
        >
        <small class="text-muted">Leave this empty to use an automatic system-generated reference number.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">
            Status <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" {{ old('status', $item->status ?? ($defaults['status'] ?? 'active')) === 'active' ? 'selected' : '' }}>
                Active
            </option>
            <option value="inactive" {{ old('status', $item->status ?? ($defaults['status'] ?? 'active')) === 'inactive' ? 'selected' : '' }}>
                Inactive
            </option>
        </select>
    </div>

    <div class="col-md-12 mb-3">
        <label for="header_description" class="form-label">Header Description</label>
        <textarea
            class="form-control"
            id="header_description"
            name="header_description"
            rows="4"
        >{{ old('header_description', $item->header_description ?? '') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route($routeName . '.index') }}" class="btn btn-secondary">Cancel</a>
</div>
