@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Confirming your card</h4>
        <p>Stripe has the checkout. This page checks again in a few seconds.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card lw-card">
                <div class="card-body">
                    <div class="lw-empty px-0 pt-2 pb-0">
                        <div class="lw-empty-icon" aria-hidden="true">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div class="lw-empty-title">Payment processing</div>
                        <p class="mb-0">Your card is being saved on the trial. You are not charged today. The plan price is taken when the trial ends.</p>
                    </div>

                    <ol class="lw-billing-steps">
                        <li>We ask Stripe whether this checkout finished.</li>
                        <li>The card is stored against your trial.</li>
                        <li>Settings opens with the trial dates.</li>
                    </ol>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('backend.billing.index') }}" class="btn lw-btn-primary">
                            {{ is_landlord_plan_user() ? 'Open Settings' : 'Back to Billing & Plan' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .lw-billing-steps {
        margin: 1.25rem 0 0;
        padding-left: 1.15rem;
        color: var(--lw-muted, #5b6573);
    }
    .lw-billing-steps li + li {
        margin-top: 0.45rem;
    }
</style>

<script>
    (function () {
        var key = 'billing-success-checks';
        var checks = Number(window.sessionStorage.getItem(key) || 0);
        if (checks >= 4) {
            return;
        }
        window.sessionStorage.setItem(key, String(checks + 1));
        window.setTimeout(function () {
            window.location.reload();
        }, 4000);
    })();
</script>
@endsection
