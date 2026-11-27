{{--
    Trial and automatic payment terms, accepted before the signup code is sent.
    Expects $plan and $billingCycle.
--}}
@php
    $annual = $billingCycle === 'annual';
    $price = $annual ? $plan->formattedAnnualPrice() : $plan->formattedMonthlyPrice();
    $period = $annual ? 'year' : 'month';
    $trialDays = (int) $plan->trial_days;
    $chargeDate = rs_date(now()->addDays($trialDays));
@endphp
<div class="modal fade" id="trial-terms-modal" tabindex="-1" aria-labelledby="trial-terms-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content text-start">
            <div class="modal-header">
                <h5 class="modal-title" id="trial-terms-title">
                    {{ $trialDays > 0 ? 'Your '.$trialDays.'-day free trial and automatic payment' : 'Your subscription and automatic payment' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered mb-3" data-trial-summary>
                    <tbody>
                        <tr><th scope="row">Plan</th><td>{{ $plan->name }}, {{ $price }} a {{ $period }}</td></tr>
                        @if($trialDays > 0)
                            <tr><th scope="row">Today</th><td><strong>£0.00</strong> (free trial starts)</td></tr>
                            <tr><th scope="row">About {{ $chargeDate }}</th><td><strong>{{ $price }}</strong> taken from your card automatically</td></tr>
                        @else
                            <tr><th scope="row">Today</th><td><strong>{{ $price }}</strong> taken from your card</td></tr>
                        @endif
                        <tr><th scope="row">After that</th><td>{{ $price }} every {{ $period }} until you cancel</td></tr>
                    </tbody>
                </table>

                <ol class="small ps-3 mb-0">
                    @if($trialDays > 0)
                        <li class="mb-2"><strong>Free trial.</strong> You have {{ $trialDays }} days from the day you verify your account. Nothing is charged during the trial.</li>
                    @endif
                    <li class="mb-2"><strong>Your card.</strong> After verifying you are taken to Stripe's secure checkout to add your card. ResiSquare does not store your card number. Stripe may show the price in your local currency.</li>
                    <li class="mb-2"><strong>Automatic payment.</strong>
                        @if($trialDays > 0)
                            When the trial ends, {{ $price }} is charged to that card automatically, and then again every {{ $period }}, until you cancel.
                        @else
                            {{ $price }} is charged to that card now and then every {{ $period }}, until you cancel.
                        @endif
                    </li>
                    <li class="mb-2"><strong>Cancelling.</strong> Cancel at any time in Settings, then Manage billing.
                        @if($trialDays > 0)
                            If you cancel before the trial ends, you are not charged.
                        @endif
                        If you cancel after a payment, you keep access until the end of that paid {{ $period }} and are not charged again.</li>
                    <li class="mb-2"><strong>If a payment fails.</strong> Your account is marked past due and you are asked to update your card in Manage billing. The payment may be tried again. If it cannot be collected, the subscription may be cancelled and access to your workspace paused.</li>
                    @if($trialDays > 0)
                        <li class="mb-2"><strong>Reminder.</strong> We email and notify you the day before your trial ends.</li>
                        <li><strong>If you do not add a card.</strong> Your trial still runs. When it ends, access pauses until you subscribe. Your data is kept.</li>
                    @endif
                </ol>
            </div>
            <div class="modal-footer flex-column align-items-stretch">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="trial-terms-accept">
                    <label class="form-check-label small" for="trial-terms-accept">
                        I have read these terms and agree that {{ $price }} a {{ $period }} is charged to my card automatically{{ $trialDays > 0 ? ' after the free trial' : '' }}, until I cancel.
                    </label>
                </div>
                <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="button" class="btn btn_secondary" id="trial-terms-agree" disabled>Agree and send code</button>
                </div>
            </div>
        </div>
    </div>
</div>
