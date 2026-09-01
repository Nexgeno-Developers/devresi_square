<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Services\Saas\AccountLimitService;
use App\Services\Saas\StripeCheckoutService;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use AuthorizesBillingAccess;

    public function index(Request $request, AccountLimitService $limitService)
    {
        $account = $this->billingAccount($request);

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

    public function success(
        Request $request,
        StripeCheckoutService $checkoutService,
        StripeWebhookService $webhookService
    )
    {
        $account = $this->billingAccount($request);
        // Avoid the conventional `session_id` parameter name: some ModSecurity
        // rules mistake it for an attempt to set a PHP session identifier.
        $sessionId = (string) $request->query(
            'checkout_ref',
            $request->query('session_id', '')
        );

        if ($sessionId !== '') {
            try {
                $session = $checkoutService->retrieveCheckoutSession($sessionId);
                $sessionAccountId = (int) ($session->metadata->account_id ?? 0);

                abort_unless($sessionAccountId === (int) $account->id, 403);
                $webhookService->handleCheckoutSessionCompleted($session);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('backend.billing.index')
                    ->with('error', 'Stripe checkout completed, but subscription status is still syncing.');
            }
        }

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

        return view('backend.saas.billing.cancel');
    }
}
