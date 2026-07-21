@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="alert alert-info mb-3">
        <h4 class="alert-heading mb-2">Payment processing</h4>
        <p class="mb-0">Stripe has received the checkout result. Your subscription will activate shortly after the webhook confirms the payment and subscription status.</p>
    </div>

    <a href="{{ route('backend.billing.index') }}" class="btn btn-primary">Back to Billing &amp; Plan</a>
</div>
@endsection
