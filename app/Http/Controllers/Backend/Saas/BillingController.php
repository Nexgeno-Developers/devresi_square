<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Services\Saas\AccountLimitService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use AuthorizesBillingAccess;

    public function index(Request $request, AccountLimitService $limitService)
    {
        $account = $this->billingAccount($request);
        $this->authorize('view', $account);
        app(\App\Services\Onboarding\LandlordOnboardingService::class)
            ->syncOverlaySessionForPage($request->user(), $account, false);

        $subscription = $account->subscriptions()
            ->with(['plan', 'accountSubscriptionAddons.addon'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'trialing' THEN 1 WHEN 'past_due' THEN 2 WHEN 'cancelled' THEN 3 WHEN 'expired' THEN 4 ELSE 5 END")
            ->latest()
            ->first();

        $limitSummary = $limitService->summary($account);
        $availableAddons = Addon::query()
            ->where('is_active', true)
            ->orderBy('addon_type')
            ->orderBy('name')
            ->get();
        $activeAddons = $subscription
            ? $subscription->accountSubscriptionAddons->where('status', 'active')
            : collect();

        return view('backend.saas.billing.index', compact(
            'account',
            'subscription',
            'limitSummary',
            'availableAddons',
            'activeAddons'
        ));
    }

    public function success(Request $request)
    {
        $account = $this->billingAccount($request);
        $this->authorize('view', $account);

        // Checkout success is a thank-you page only. Stripe webhooks activate the plan.
        $account->refresh();

        if (in_array($account->status, ['trialing', 'active'], true)) {
            return redirect()
                ->route('backend.dashboard')
                ->with('success', 'Your subscription is active.');
        }

        return view('backend.saas.billing.success');
    }

    public function cancel(Request $request)
    {
        $this->billingAccount($request);
        $this->authorize('view', current_account());

        return view('backend.saas.billing.cancel');
    }
}
