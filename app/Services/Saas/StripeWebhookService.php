<?php

namespace App\Services\Saas;

use App\Models\AccountSubscription;
use App\Models\AccountSubscriptionAddon;
use App\Models\Addon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\StripeObject;
use Stripe\Subscription;

class StripeWebhookService
{
    public function handle(object $event): void
    {
        $object = $event->data->object ?? null;

        match ($event->type ?? null) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($object),
            'customer.subscription.created' => $this->handleSubscriptionCreatedOrUpdated($object),
            'customer.subscription.updated' => $this->handleSubscriptionCreatedOrUpdated($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            default => Log::info('Stripe webhook ignored: unhandled event type', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
            ]),
        };
    }

    public function handleCheckoutSessionCompleted(mixed $session): void
    {
        if (! $session || $this->metadataValue($session->metadata ?? [], 'type') !== 'plan_subscription') {
            return;
        }

        $subscription = $this->subscriptionFromMetadata($session->metadata ?? []);
        if (! $subscription) {
            Log::warning('Stripe checkout session missing local subscription metadata', [
                'checkout_session_id' => $this->stripeId($session),
            ]);
            return;
        }

        $stripeSubscriptionId = $this->stripeId($session->subscription ?? null);
        if ($stripeSubscriptionId) {
            $subscription->forceFill([
                'stripe_subscription_id' => $stripeSubscriptionId,
            ])->save();

            if ($stripeSubscription = $this->retrieveSubscription($stripeSubscriptionId)) {
                $this->syncSubscriptionFromStripe($stripeSubscription, $subscription);
            }
        }
    }

    private function handleSubscriptionCreatedOrUpdated(mixed $stripeSubscription): void
    {
        if (! $stripeSubscription) {
            return;
        }

        $this->syncSubscriptionFromStripe($stripeSubscription);
    }

    private function handleCheckoutSessionExpired(mixed $session): void
    {
        if (! $session || $this->metadataValue($session->metadata ?? [], 'type') !== 'plan_subscription') {
            return;
        }

        $subscription = $this->subscriptionFromMetadata($session->metadata ?? []);
        if (! $subscription || $subscription->stripe_subscription_id) {
            return;
        }

        $subscription->forceFill([
            'status' => 'expired',
            'cancelled_at' => $subscription->cancelled_at ?: now(),
        ])->save();

        $this->syncAccountStatus($subscription, 'expired');
    }

    private function handleSubscriptionDeleted(mixed $stripeSubscription): void
    {
        $stripeSubscriptionId = $this->stripeId($stripeSubscription);
        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = AccountSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if (! $subscription) {
            Log::warning('Stripe subscription deleted for missing local subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        $subscription->forceFill([
            'status' => 'cancelled',
            'cancel_at_period_end' => false,
            'cancelled_at' => $subscription->cancelled_at ?: now(),
        ])->save();

        $this->syncAccountStatus($subscription, 'cancelled');

        AccountSubscriptionAddon::query()
            ->where('account_subscription_id', $subscription->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }

    private function handleInvoicePaymentSucceeded(mixed $invoice): void
    {
        $stripeSubscriptionId = $this->invoiceSubscriptionId($invoice);
        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = AccountSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if (! $subscription) {
            Log::warning('Stripe invoice payment succeeded for missing local subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        if ($stripeSubscription = $this->retrieveSubscription($stripeSubscriptionId)) {
            $this->syncSubscriptionFromStripe($stripeSubscription, $subscription);

            return;
        }

        $updates = ['status' => 'active'];
        if ($periodStart = $this->invoicePeriodTimestamp($invoice, 'start')) {
            $updates['current_period_start'] = $this->carbonFromTimestamp($periodStart);
        }
        if ($periodEnd = $this->invoicePeriodTimestamp($invoice, 'end')) {
            $updates['current_period_end'] = $this->carbonFromTimestamp($periodEnd);
        }

        $subscription->forceFill($updates)->save();
        $this->syncAccountStatus($subscription, 'active');
    }

    private function handleInvoicePaymentFailed(mixed $invoice): void
    {
        $stripeSubscriptionId = $this->invoiceSubscriptionId($invoice);
        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = AccountSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if (! $subscription) {
            Log::warning('Stripe invoice payment failed for missing local subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        $subscription->forceFill([
            'status' => 'past_due',
        ])->save();

        $this->syncAccountStatus($subscription, 'past_due');
    }

    private function syncSubscriptionFromStripe(mixed $stripeSubscription, ?AccountSubscription $subscription = null): ?AccountSubscription
    {
        $stripeSubscriptionId = $this->stripeId($stripeSubscription);
        if (! $stripeSubscriptionId) {
            return null;
        }

        $subscription ??= AccountSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        $subscription ??= $this->subscriptionFromMetadata($stripeSubscription->metadata ?? []);

        if (! $subscription) {
            Log::warning('Stripe subscription sync missing local subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return null;
        }

        $stripeStatus = (string) ($stripeSubscription->status ?? '');
        $mappedStatus = $this->mapStripeStatus($stripeStatus);
        $priceId = $this->subscriptionPriceId($stripeSubscription);

        $updates = [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'status' => $mappedStatus,
            'cancel_at_period_end' => (bool) ($stripeSubscription->cancel_at_period_end ?? false),
        ];

        if ($priceId) {
            $updates['stripe_price_id'] = $priceId;
        }

        if ($periodStart = $this->subscriptionPeriodTimestamp($stripeSubscription, 'current_period_start')) {
            $updates['current_period_start'] = $this->carbonFromTimestamp($periodStart);
        }

        if ($periodEnd = $this->subscriptionPeriodTimestamp($stripeSubscription, 'current_period_end')) {
            $updates['current_period_end'] = $this->carbonFromTimestamp($periodEnd);
        }

        if ($trialStart = $this->timestampValue($stripeSubscription, 'trial_start')) {
            $updates['trial_started_at'] = $this->carbonFromTimestamp($trialStart);
        }

        if ($trialEnd = $this->timestampValue($stripeSubscription, 'trial_end')) {
            $updates['trial_ends_at'] = $this->carbonFromTimestamp($trialEnd);
        }

        if ($mappedStatus === 'cancelled') {
            $updates['cancelled_at'] = $subscription->cancelled_at ?: now();
        }

        $subscription->forceFill($updates)->save();

        $this->syncAccountStatus($subscription, $mappedStatus);
        $this->syncAddonItems($stripeSubscription, $subscription);

        return $subscription;
    }

    private function syncAddonItems(mixed $stripeSubscription, AccountSubscription $subscription): void
    {
        $seenStripeItemIds = [];

        foreach ($this->subscriptionItems($stripeSubscription) as $item) {
            $addonId = $this->metadataValue($item->metadata ?? [], 'addon_id');
            if (! $addonId) {
                continue;
            }

            $addon = Addon::find($addonId);
            if (! $addon) {
                Log::warning('Stripe subscription addon item references missing addon', [
                    'addon_id' => $addonId,
                    'stripe_subscription_item_id' => $this->stripeId($item),
                    'account_subscription_id' => $subscription->id,
                ]);
                continue;
            }

            $stripeItemId = $this->stripeId($item);
            if (! $stripeItemId) {
                continue;
            }

            $seenStripeItemIds[] = $stripeItemId;
            $priceId = $this->itemPriceId($item);
            $quantity = max(1, (int) ($item->quantity ?? 1));

            $subscriptionAddon = AccountSubscriptionAddon::query()
                ->where('stripe_subscription_item_id', $stripeItemId)
                ->first();

            $subscriptionAddon ??= AccountSubscriptionAddon::query()
                ->where('account_subscription_id', $subscription->id)
                ->where('account_id', $subscription->account_id)
                ->where('addon_id', $addon->id)
                ->where('status', 'active')
                ->first();

            $attributes = [
                'account_subscription_id' => $subscription->id,
                'account_id' => $subscription->account_id,
                'addon_id' => $addon->id,
                'quantity' => $quantity,
                'billing_cycle' => $subscription->billing_cycle,
                'status' => 'active',
                'stripe_subscription_item_id' => $stripeItemId,
                'stripe_price_id' => $priceId,
                'price_at_purchase_minor' => $subscription->billing_cycle === 'annual'
                    ? $addon->annual_price_minor
                    : $addon->monthly_price_minor,
                'addon_name_at_purchase' => $addon->name,
            ];

            if ($subscriptionAddon) {
                $subscriptionAddon->update($attributes);
            } else {
                AccountSubscriptionAddon::create($attributes);
            }
        }

        if ($seenStripeItemIds !== []) {
            AccountSubscriptionAddon::query()
                ->where('account_subscription_id', $subscription->id)
                ->where('status', 'active')
                ->whereNotNull('stripe_subscription_item_id')
                ->whereNotIn('stripe_subscription_item_id', $seenStripeItemIds)
                ->update(['status' => 'cancelled']);
        }
    }

    private function subscriptionFromMetadata(mixed $metadata): ?AccountSubscription
    {
        $subscriptionId = $this->metadataValue($metadata, 'account_subscription_id');
        if (! $subscriptionId) {
            Log::warning('Stripe metadata missing account_subscription_id');
            return null;
        }

        $query = AccountSubscription::query()->whereKey($subscriptionId);

        if ($accountId = $this->metadataValue($metadata, 'account_id')) {
            $query->where('account_id', $accountId);
        }

        return $query->first();
    }

    private function syncAccountStatus(AccountSubscription $subscription, string $subscriptionStatus): void
    {
        $subscription->loadMissing('account');

        if (! $subscription->account) {
            Log::warning('Stripe subscription status sync missing account', [
                'account_subscription_id' => $subscription->id,
                'account_id' => $subscription->account_id,
            ]);

            return;
        }

        if (! $this->shouldSyncAccountStatus($subscription)) {
            return;
        }

        $accountStatus = match ($subscriptionStatus) {
            'trialing', 'active', 'past_due', 'cancelled' => $subscriptionStatus,
            'expired' => 'cancelled',
            default => null,
        };

        if ($accountStatus) {
            $accountUpdates = ['status' => $accountStatus];

            if ($subscription->trial_started_at) {
                $accountUpdates['trial_started_at'] = $subscription->trial_started_at;
            }
            if ($subscription->trial_ends_at) {
                $accountUpdates['trial_ends_at'] = $subscription->trial_ends_at;
            }

            $subscription->account->forceFill($accountUpdates)->save();
        }
    }

    private function shouldSyncAccountStatus(AccountSubscription $subscription): bool
    {
        $latestSubscriptionId = AccountSubscription::query()
            ->where('account_id', $subscription->account_id)
            ->latest()
            ->value('id');

        return (int) $latestSubscriptionId === (int) $subscription->id;
    }

    private function retrieveSubscription(string $stripeSubscriptionId): mixed
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            return null;
        }

        Stripe::setApiKey($secret);

        try {
            return Subscription::retrieve($stripeSubscriptionId);
        } catch (\Throwable) {
            return null;
        }
    }

    private function subscriptionItems(mixed $stripeSubscription): array
    {
        $items = $stripeSubscription->items->data ?? [];

        return is_array($items) ? $items : [];
    }

    private function subscriptionPriceId(mixed $stripeSubscription): ?string
    {
        $firstPriceId = null;

        foreach ($this->subscriptionItems($stripeSubscription) as $item) {
            $priceId = $this->itemPriceId($item);
            $firstPriceId ??= $priceId;

            if (! $this->metadataValue($item->metadata ?? [], 'addon_id')) {
                return $priceId;
            }
        }

        return $firstPriceId;
    }

    private function itemPriceId(mixed $item): ?string
    {
        return $this->stripeId($item->price ?? null);
    }

    private function invoiceSubscriptionId(mixed $invoice): ?string
    {
        return $this->stripeId($invoice->subscription ?? null)
            ?: $this->stripeId($invoice->parent->subscription_details->subscription ?? null);
    }

    private function invoicePeriodTimestamp(mixed $invoice, string $field): ?int
    {
        $line = $invoice->lines->data[0] ?? null;
        $timestamp = $line?->period?->{$field} ?? null;

        return $timestamp ? (int) $timestamp : null;
    }

    private function subscriptionPeriodTimestamp(mixed $stripeSubscription, string $field): ?int
    {
        $timestamp = $stripeSubscription->{$field} ?? null;
        if ($timestamp) {
            return (int) $timestamp;
        }

        $firstItem = $this->subscriptionItems($stripeSubscription)[0] ?? null;
        $timestamp = $firstItem?->{$field} ?? null;

        return $timestamp ? (int) $timestamp : null;
    }

    private function carbonFromTimestamp(int $timestamp): Carbon
    {
        return Carbon::createFromTimestamp($timestamp);
    }

    private function timestampValue(mixed $object, string $field): ?int
    {
        $timestamp = $object->{$field} ?? null;

        return $timestamp ? (int) $timestamp : null;
    }

    private function metadataValue(mixed $metadata, string $key): ?string
    {
        $metadata = $this->toArray($metadata);
        $value = $metadata[$key] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    private function toArray(mixed $value): array
    {
        if ($value instanceof StripeObject) {
            return $value->toArray();
        }

        return is_array($value) ? $value : [];
    }

    private function stripeId(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && isset($value->id)) {
            return (string) $value->id;
        }

        return null;
    }

    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled', 'cancelled' => 'cancelled',
            'unpaid', 'incomplete' => 'past_due',
            'paused' => 'cancelled',
            'incomplete_expired' => 'expired',
            default => 'past_due',
        };
    }
}
