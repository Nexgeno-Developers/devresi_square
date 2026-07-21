<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Services\Saas\StripeCheckoutService;
use Illuminate\Http\Request;

class AddonCheckoutController extends Controller
{
    use AuthorizesBillingAccess;

    public function checkout(Addon $addon, Request $request, StripeCheckoutService $service)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $account = $this->billingAccount($request);

        if (! $addon->is_active) {
            flash('This addon is not available.')->error();

            return back();
        }

        $subscription = $account->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'trialing' THEN 1 ELSE 2 END")
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->stripe_subscription_id) {
            flash('Please activate your main subscription before buying addons.')->error();

            return back();
        }

        try {
            $service->addAddonToSubscription($account, $subscription, $addon, (int) $validated['quantity']);
            flash('Addon activated successfully.')->success();
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();
        }

        return redirect()->route('backend.billing.index');
    }
}
