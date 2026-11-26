<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Services\Saas\StripeCheckoutService;
use Illuminate\Http\Request;

class SubscriptionCheckoutController extends Controller
{
    use AuthorizesBillingAccess;

    public function checkout(Request $request, StripeCheckoutService $service)
    {
        $account = $this->billingAccount($request);

        $subscription = $account->subscriptions()
            ->with('plan')
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'trialing' THEN 1 WHEN 'past_due' THEN 2 ELSE 3 END")
            ->latest()
            ->first();

        // A cancelled or expired owner buys the same plan again; there is no trial the second time.
        $subscription ??= $service->renewableSubscription($account);

        if (! $subscription) {
            flash($account->status === 'cancelled' && $account->status_changed_by
                ? 'This account was closed by Resisquare. Please contact support to restore access.'
                : 'No current subscription was found for this account.')->error();

            return back();
        }

        if ($subscription->stripe_subscription_id && in_array($subscription->status, ['trialing', 'active'], true)) {
            flash('Subscription is already active in Stripe.')->success();

            return redirect()->route('backend.billing.index');
        }

        try {
            return redirect()->away($service->createPlanCheckoutSession($account, $subscription));
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back();
        }
    }
}
