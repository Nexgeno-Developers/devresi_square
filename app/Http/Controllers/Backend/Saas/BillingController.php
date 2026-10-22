<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Backend\Saas\Concerns\AuthorizesBillingAccess;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Addon;
use App\Services\Saas\AccountLimitService;
use App\Services\Saas\StripeCheckoutService;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function success(Request $request, StripeCheckoutService $checkout, StripeWebhookService $webhooks)
    {
        $account = $this->billingAccount($request);
        $this->authorize('view', $account);

        $this->applyReturnedCheckout($request, $account, $checkout, $webhooks);
        $account->refresh();

        if (in_array($account->status, ['trialing', 'active'], true)) {
            $message = $account->status === 'trialing'
                ? 'Your card is saved. The trial continues'
                    .($account->trial_ends_at ? ' until '.$account->trial_ends_at->timezone(config('app.timezone'))->format('j M Y') : '')
                    .'. The plan price is charged when the trial ends.'
                : 'Your subscription is active.';

            flash($message)->success();

            return redirect()->route('backend.billing.index');
        }

        return view('backend.saas.billing.success');
    }

    private function applyReturnedCheckout(
        Request $request,
        Account $account,
        StripeCheckoutService $checkout,
        StripeWebhookService $webhooks
    ): void {
        $checkoutRef = (string) $request->session()->get('billing_checkout_session_id', '');
        if (! str_starts_with($checkoutRef, 'cs_')) {
            $checkoutRef = (string) $request->query('checkout_ref', '');
        }
        if (! str_starts_with($checkoutRef, 'cs_')) {
            return;
        }

        try {
            $session = $checkout->retrieveCheckoutSession($checkoutRef);
        } catch (\Throwable $exception) {
            Log::warning('Stripe checkout return could not be loaded', [
                'account_id' => $account->id,
                'checkout_ref' => $checkoutRef,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (($session->status ?? null) !== 'complete' || ! $this->checkoutSessionMatchesAccount($session, $account)) {
            Log::warning('Stripe checkout return ignored', [
                'account_id' => $account->id,
                'checkout_ref' => $checkoutRef,
                'session_status' => $session->status ?? null,
            ]);

            return;
        }

        $webhooks->handleCheckoutSessionCompleted($session);
        $request->session()->forget('billing_checkout_session_id');
    }

    private function checkoutSessionMatchesAccount(object $session, Account $account): bool
    {
        $metadata = $session->metadata ?? [];
        $metadataAccountId = null;
        if (is_array($metadata)) {
            $metadataAccountId = $metadata['account_id'] ?? null;
        } elseif (is_object($metadata)) {
            $metadataAccountId = $metadata->account_id ?? null;
        }

        $ids = array_filter([
            $metadataAccountId !== null && $metadataAccountId !== '' ? (string) $metadataAccountId : null,
            isset($session->client_reference_id) && $session->client_reference_id !== ''
                ? (string) $session->client_reference_id
                : null,
        ]);

        if ($ids === []) {
            return false;
        }

        foreach ($ids as $id) {
            if ($id !== (string) $account->id) {
                return false;
            }
        }

        return true;
    }

    public function cancel(Request $request)
    {
        $this->billingAccount($request);
        $this->authorize('view', current_account());

        return view('backend.saas.billing.cancel');
    }
}
