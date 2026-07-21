@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="alert alert-warning mb-3">
        <h4 class="alert-heading mb-2">Checkout cancelled</h4>
        <p class="mb-0">No subscription changes were made. You can return to billing when you are ready to activate your plan.</p>
    </div>

    <a href="{{ route('backend.billing.index') }}" class="btn btn-primary">Back to Billing &amp; Plan</a>
</div>
@endsection
