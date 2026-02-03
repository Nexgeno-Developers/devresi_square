@csrf
<div class="mb-3">
    <label>Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $transaction_category->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label>Code</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $transaction_category->code ?? '') }}">
</div>

<div class="mb-3">
    <label>Type</label>
    <select name="is_income" class="form-control" required>
        <option value="1" {{ old('is_income', $transaction_category->is_income ?? '') == 1 ? 'selected' : '' }}>Income</option>
        <option value="0" {{ old('is_income', $transaction_category->is_income ?? '') == 0 ? 'selected' : '' }}>Expense</option>
    </select>
</div>

<div class="mb-3">
    <label>Status</label>
    <select name="is_active" class="form-control" required>
        <option value="1" {{ old('is_active', $transaction_category->is_active ?? '') == 1 ? 'selected' : '' }}>Active</option>
        <option value="0" {{ old('is_active', $transaction_category->is_active ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
    </select>
</div>

<div class="mb-3">
    <label>System Category?</label>
    <select name="is_system" class="form-control">
        <option value="0" {{ old('is_system', $transaction_category->is_system ?? '') == 0 ? 'selected' : '' }}>No</option>
        <option value="1" {{ old('is_system', $transaction_category->is_system ?? '') == 1 ? 'selected' : '' }}>Yes</option>
    </select>
</div>
