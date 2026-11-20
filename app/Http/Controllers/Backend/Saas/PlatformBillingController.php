<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\Account;
use App\Models\AccountSubscription;

class PlatformBillingController extends BaseSaasController
{
    public function index()
    {
        $subscriptions = AccountSubscription::query()
            ->with(['account.owner', 'plan', 'activeAddons'])
            ->whereIn('status', ['trialing', 'active', 'past_due', 'cancelled'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $live = AccountSubscription::query()
            ->with('activeAddons')
            ->whereIn('status', ['active', 'past_due'])
            ->get();

        $mrrForSubscription = function (AccountSubscription $subscription) {
            $planAmount = (int) $subscription->price_at_signup_minor;
            $planMrr = $subscription->billing_cycle === 'annual' ? $planAmount / 12 : $planAmount;
            $addonMrr = $subscription->activeAddons->sum(function ($addon) {
                $amount = (int) $addon->price_at_purchase_minor * max(1, (int) $addon->quantity);

                return $addon->billing_cycle === 'annual' ? $amount / 12 : $amount;
            });

            return $planMrr + $addonMrr;
        };

        // Never add different currencies together: one MRR/ARR figure per signup currency.
        $mrrByCurrency = $live
            ->groupBy(fn (AccountSubscription $s) => strtoupper((string) ($s->currency_at_signup ?: 'GBP')))
            ->map(fn ($group) => $group->sum($mrrForSubscription) / 100)
            ->sortKeys();
        $gbpMrr = (float) ($mrrByCurrency['GBP'] ?? 0);

        $metrics = [
            'mrr' => $gbpMrr,
            'arr' => $gbpMrr * 12,
            'mrr_by_currency' => $mrrByCurrency->all(),
            'trialing' => Account::query()->where('status', 'trialing')->count(),
            'active' => Account::query()->where('status', 'active')->count(),
            'past_due' => Account::query()->where('status', 'past_due')->count(),
            'stripe_linked' => AccountSubscription::query()
                ->whereNotNull('stripe_subscription_id')
                ->where('stripe_subscription_id', '!=', '')
                ->count(),
        ];

        return view('backend.saas.platform-billing.index', compact('subscriptions', 'metrics'));
    }
}
