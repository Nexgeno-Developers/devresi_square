<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\AccountSubscriptionAddon;
use App\Models\Addon;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\SubscriptionItem;

class StripeCheckoutService
{
    private const ACTIVE_SUBSCRIPTION_STATUSES = ['active', 'trialing'];

    public function createPlanCheckoutSession(Account $account, AccountSubscription $subscription): string
    {
        if ((int) $subscription->account_id !== (int) $account->id) {
            throw new RuntimeException('Subscription does not belong to the current account.');
        }

        $subscription->loadMissing('plan');

        if (! $subscription->plan) {
            throw new RuntimeException('Selected plan is not available.');
        }

        $this->configureStripe();

        $metadata = [
            'account_id' => (string) $account->id,
            'account_subscription_id' => (string) $subscription->id,
            'plan_id' => (string) $subscription->plan_id,
            'billing_cycle' => (string) $subscription->billing_cycle,
            'type' => 'plan_subscription',
        ];

        $sessionData = [
            'mode' => 'subscription',
            'customer' => $this->ensureStripeCustomer($account),
            'line_items' => [$this->planLineItem($subscription)],
            'success_url' => url('/admin/billing/success') . '?checkout_ref={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('/admin/billing/cancel'),
            'client_reference_id' => (string) $account->id,
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        if (
            $subscription->status === 'trialing'
            && $subscription->trial_ends_at
            && $subscription->trial_ends_at->isFuture()
        ) {
            $sessionData['subscription_data']['trial_end'] = $subscription->trial_ends_at->timestamp;
            $sessionData['subscription_data']['trial_settings'] = [
                'end_behavior' => [
                    'missing_payment_method' => 'cancel',
                ],
            ];
        }

        $session = Session::create($sessionData);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return (string) $session->url;
    }

    public function createAddonCheckoutSession(Account $account, Addon $addon, int $quantity = 1): string
    {
        $subscription = $account->subscriptions()
            ->whereIn('status', self::ACTIVE_SUBSCRIPTION_STATUSES)
            ->latest()
            ->first();

        if (! $subscription) {
            throw new RuntimeException('Please activate your main subscription before buying addons.');
        }

        $priceId = $this->addonPriceId($addon, $subscription->billing_cycle);
        if (! $priceId) {
            throw new RuntimeException('Stripe price ID is missing for this addon. Please contact support.');
        }

        $this->configureStripe();

        $metadata = [
            'account_id' => (string) $account->id,
            'account_subscription_id' => (string) $subscription->id,
            'addon_id' => (string) $addon->id,
            'billing_cycle' => (string) $subscription->billing_cycle,
            'quantity' => (string) $quantity,
            'type' => 'addon_subscription',
        ];

        $session = Session::create([
            'mode' => 'subscription',
            'customer' => $this->ensureStripeCustomer($account),
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => $quantity,
                ],
            ],
            'success_url' => url('/admin/billing/success') . '?checkout_ref={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('/admin/billing/cancel'),
            'client_reference_id' => (string) $account->id,
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return (string) $session->url;
    }

    public function ensureStripeCustomer(Account $account): string
    {
        if ($account->stripe_customer_id) {
            return (string) $account->stripe_customer_id;
        }

        $this->configureStripe();

        $customer = Customer::create([
            'name' => $account->account_name ?: 'Account #' . $account->id,
            'email' => $account->billing_email,
            'phone' => $account->billing_phone,
            'metadata' => [
                'account_id' => (string) $account->id,
            ],
        ]);

        $account->forceFill([
            'stripe_customer_id' => (string) $customer->id,
        ])->save();

        return (string) $customer->id;
    }

    public function createBillingPortalSession(Account $account): string
    {
        $this->configureStripe();

        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $this->ensureStripeCustomer($account),
            'return_url' => route('backend.billing.index'),
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a billing portal URL.');
        }

        return (string) $session->url;
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        $this->configureStripe();

        return Session::retrieve([
            'id' => $sessionId,
            'expand' => ['subscription'],
        ]);
    }

    public function addAddonToSubscription(
        Account $account,
        AccountSubscription $subscription,
        Addon $addon,
        int $quantity = 1
    ): AccountSubscriptionAddon {
        if ((int) $subscription->account_id !== (int) $account->id) {
            throw new RuntimeException('Subscription does not belong to the current account.');
        }

        if (! in_array($subscription->status, self::ACTIVE_SUBSCRIPTION_STATUSES, true)) {
            throw new RuntimeException('Please activate your main subscription before buying addons.');
        }

        if (! $subscription->stripe_subscription_id) {
            throw new RuntimeException('Please activate your main subscription before buying addons.');
        }

        if (! $addon->is_active) {
            throw new RuntimeException('This addon is not available.');
        }

        $priceId = $this->addonPriceId($addon, $subscription->billing_cycle);
        if (! $priceId) {
            throw new RuntimeException('Stripe price ID is missing for this addon. Please contact support.');
        }

        $this->configureStripe();

        $existingAddon = AccountSubscriptionAddon::query()
            ->where('account_subscription_id', $subscription->id)
            ->where('account_id', $account->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->first();

        $newQuantity = $quantity;
        if ($existingAddon) {
            if (! $addon->is_stackable) {
                throw new RuntimeException('This addon is already active for your subscription.');
            }

            $newQuantity = (int) $existingAddon->quantity + $quantity;
        }

        $metadata = [
            'account_id' => (string) $account->id,
            'addon_id' => (string) $addon->id,
            'account_subscription_id' => (string) $subscription->id,
        ];

        if ($existingAddon?->stripe_subscription_item_id) {
            $stripeItem = SubscriptionItem::update($existingAddon->stripe_subscription_item_id, [
                'quantity' => $newQuantity,
                'metadata' => $metadata,
            ]);
        } else {
            $stripeItem = SubscriptionItem::create([
                'subscription' => $subscription->stripe_subscription_id,
                'price' => $priceId,
                'quantity' => $newQuantity,
                'metadata' => $metadata,
            ]);
        }

        $snapshot = [
            'quantity' => $newQuantity,
            'billing_cycle' => $subscription->billing_cycle,
            'status' => 'active',
            'stripe_subscription_item_id' => (string) $stripeItem->id,
            'stripe_price_id' => $priceId,
            'price_at_purchase_minor' => $this->addonPriceSnapshot($addon, $subscription->billing_cycle),
            'addon_name_at_purchase' => $addon->name,
        ];

        if ($existingAddon) {
            $existingAddon->update($snapshot);

            return $existingAddon->refresh();
        }

        return AccountSubscriptionAddon::create([
            'account_subscription_id' => $subscription->id,
            'account_id' => $account->id,
            'addon_id' => $addon->id,
            ...$snapshot,
        ]);
    }

    private function configureStripe(): void
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured. Set STRIPE_SECRET in .env.');
        }

        Stripe::setApiKey($secret);
    }

    private function planPriceId(AccountSubscription $subscription): ?string
    {
        $plan = $subscription->plan;

        return $subscription->billing_cycle === 'annual'
            ? $plan?->stripe_annual_price_id
            : $plan?->stripe_monthly_price_id;
    }

    private function planLineItem(AccountSubscription $subscription): array
    {
        if ($priceId = $this->planPriceId($subscription)) {
            return [
                'price' => $priceId,
                'quantity' => 1,
            ];
        }

        $plan = $subscription->plan;
        $unitAmount = $subscription->billing_cycle === 'annual'
            ? $plan?->annual_price_minor
            : $plan?->monthly_price_minor;

        if (! $plan || ! is_numeric($unitAmount) || (int) $unitAmount < 1) {
            throw new RuntimeException('A valid subscription price is not configured for this plan.');
        }

        return [
            'price_data' => [
                'currency' => strtolower($plan->currency ?: 'GBP'),
                'unit_amount' => (int) $unitAmount,
                'recurring' => [
                    'interval' => $subscription->billing_cycle === 'annual' ? 'year' : 'month',
                ],
                'product_data' => [
                    'name' => $plan->name,
                    'description' => $plan->description ?: $plan->name . ' subscription',
                    'metadata' => [
                        'plan_id' => (string) $plan->id,
                        'plan_code' => (string) $plan->code,
                    ],
                ],
            ],
            'quantity' => 1,
        ];
    }

    private function addonPriceId(Addon $addon, string $billingCycle): ?string
    {
        return $billingCycle === 'annual'
            ? $addon->stripe_annual_price_id
            : $addon->stripe_monthly_price_id;
    }

    private function addonPriceSnapshot(Addon $addon, string $billingCycle): ?int
    {
        return $billingCycle === 'annual'
            ? $addon->annual_price_minor
            : $addon->monthly_price_minor;
    }
}
