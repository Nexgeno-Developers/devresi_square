@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>New rent invoice</h2>
    <a href="{{ route('admin.finance.index') }}" class="btn btn-outline-secondary mb-3">Back</a>

    @if($tenancies->isEmpty())
        <p class="text-muted">Add a tenancy on a property and invite a tenant before issuing rent.</p>
    @else
        <form action="{{ route('admin.finance.store') }}" method="POST">
            @csrf

            <div class="form-group mb-3">
                <label for="tenancy_id">Tenancy</label>
                <select name="tenancy_id" id="tenancy_id" class="form-control @error('tenancy_id') is-invalid @enderror" required>
                    <option value="">Select a tenancy</option>
                    @foreach($tenancies as $tenancy)
                        <option value="{{ $tenancy->id }}" @selected((string) old('tenancy_id') === (string) $tenancy->id)>
                            {{ $tenancy->property?->full_address ?: ($tenancy->property?->line_1 ?: 'Property #'.$tenancy->property_id) }}
                            @if($tenancy->status) — {{ $tenancy->status }} @endif
                        </option>
                    @endforeach
                </select>
                @error('tenancy_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="tenant_user_id">Tenant</label>
                <select name="tenant_user_id" id="tenant_user_id" class="form-control @error('tenant_user_id') is-invalid @enderror" required>
                    <option value="">Select a tenant</option>
                </select>
                @error('tenant_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="amount">Amount (£)</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="issue_date">Issue date</label>
                        <input type="date" name="issue_date" id="issue_date" class="form-control @error('issue_date') is-invalid @enderror" value="{{ old('issue_date', now()->toDateString()) }}" required>
                        @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="due_date">Due date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', now()->addDays(14)->toDateString()) }}" required>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="period_start">Period start <span class="text-muted">(optional)</span></label>
                        <input type="date" name="period_start" id="period_start" class="form-control @error('period_start') is-invalid @enderror" value="{{ old('period_start') }}">
                        @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="period_end">Period end <span class="text-muted">(optional)</span></label>
                        <input type="date" name="period_end" id="period_end" class="form-control @error('period_end') is-invalid @enderror" value="{{ old('period_end') }}">
                        @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="note">Note <span class="text-muted">(optional)</span></label>
                <textarea name="note" id="note" rows="3" class="form-control @error('note') is-invalid @enderror" maxlength="2000">{{ old('note') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-secondary">Issue invoice</button>
        </form>
    @endif
</div>

@if($tenancies->isNotEmpty())
@php
    $tenancyTenants = $tenancies->mapWithKeys(function ($tenancy) {
        return [
            $tenancy->id => $tenancy->tenantMembers
                ->map(fn ($member) => [
                    'id' => $member->user_id,
                    'name' => $member->user?->name ?: $member->user?->email,
                ])
                ->values(),
        ];
    });
@endphp
@push('scripts')
<script>
(function () {
    const tenancy = document.getElementById('tenancy_id');
    const tenant = document.getElementById('tenant_user_id');
    const selected = @json(old('tenant_user_id'));
    const map = @json($tenancyTenants);

    function fillTenants() {
        const tenants = map[tenancy.value] || [];
        tenant.innerHTML = '<option value="">Select a tenant</option>';
        tenants.forEach(function (person) {
            const opt = document.createElement('option');
            opt.value = person.id;
            opt.textContent = person.name || ('Tenant #' + person.id);
            if (String(selected) === String(person.id)) opt.selected = true;
            tenant.appendChild(opt);
        });
        if (tenants.length === 1 && !selected) {
            tenant.value = tenants[0].id;
        }
    }

    tenancy.addEventListener('change', fillTenants);
    fillTenants();
})();
</script>
@endpush
@endif
@endsection
