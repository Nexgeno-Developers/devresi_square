@extends('backend.layout.app')

@section('content')
<div class="container lw-page">
    <div class="lw-hero d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
        <div>
            <h4>How tenants pay rent</h4>
            <p>Card checkout when Stripe rent is configured; otherwise tenants use your bank details.</p>
        </div>
        <a href="{{ route('admin.finance.index') }}" class="btn btn-light">Back to finance</a>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card lw-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Card payments</h5>
                    @if($cardRentReady)
                        <p class="mb-2"><span class="badge bg-success">Ready</span></p>
                        <p class="text-muted mb-0">Stripe rent keys are configured. Tenants see a Pay button on open invoices.</p>
                    @else
                        <p class="mb-2"><span class="badge bg-secondary">Off</span></p>
                        <p class="text-muted mb-0">Card rent is off for this environment. Tenants will be guided to bank transfer instead — make sure your bank details below are filled in.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card lw-card">
                <div class="card-body">
                    <h5 class="mb-3">Bank transfer details</h5>
                    <p class="text-muted small">Shown to tenants when card rent is off, and as an alternative on invoice pages.</p>

                    <form method="POST" action="{{ route('admin.finance.rent-pay.bank') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="bank_name">Bank name</label>
                                <input type="text" name="bank_name" id="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $rentBank?->bank_name) }}" required>
                                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="account_name">Account name</label>
                                <input type="text" name="account_name" id="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name', $rentBank?->account_name) }}" required>
                                @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="sort_code">Sort code</label>
                                <input type="text" name="sort_code" id="sort_code" class="form-control @error('sort_code') is-invalid @enderror" value="{{ old('sort_code', $rentBank?->sort_code) }}" required>
                                @error('sort_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="account_no">Account number</label>
                                <input type="text" name="account_no" id="account_no" class="form-control @error('account_no') is-invalid @enderror" value="{{ old('account_no', $rentBank?->account_no) }}" required>
                                @error('account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="swift_code">SWIFT / BIC <span class="text-muted">(optional)</span></label>
                                <input type="text" name="swift_code" id="swift_code" class="form-control @error('swift_code') is-invalid @enderror" value="{{ old('swift_code', $rentBank?->swift_code) }}">
                                @error('swift_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button type="submit" class="btn lw-btn-primary">Save bank details</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
