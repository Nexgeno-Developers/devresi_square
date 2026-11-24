<?php

namespace App\Services\Saas;

use App\Models\AccountSubscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Stripe;
use Stripe\Subscription;

class TrialExtensionService
{
    public function extend(AccountSubscription $subscription, int $additionalDays): AccountSubscription
    {
        if ($additionalDays < 1 || $subscription->status !== 'trialing') {
            throw new RuntimeException('Only a currently trialing subscription can be extended.');
        }

        $currentEnd = $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture()
            ? $subscription->trial_ends_at->copy()
            : now();
        $newTrialEnd = $currentEnd->addDays($additionalDays);

        if ($newTrialEnd->isAfter(now()->addDays(730))) {
            throw new RuntimeException('Stripe allows a trial end no more than two years in the future.');
        }

        if ($subscription->stripe_subscription_id) {
            $secret = config('services.stripe.secret');
            if (! $secret) {
                throw new RuntimeException('Stripe secret key is not configured.');
            }

            Stripe::setApiKey($secret);
            // Our copy can lag behind Stripe. Putting a paying subscription back on trial would stop
            // charging the customer, so only a subscription Stripe itself has on trial is extended.
            $stripeStatus = (string) Subscription::retrieve($subscription->stripe_subscription_id)->status;
            if ($stripeStatus !== 'trialing') {
                throw new RuntimeException('Stripe shows this subscription as '.$stripeStatus.', not on trial, so the trial was not extended.');
            }
            Subscription::update($subscription->stripe_subscription_id, [
                'trial_end' => $newTrialEnd->timestamp,
                'proration_behavior' => 'none',
            ]);
        }

        return DB::transaction(function () use ($subscription, $newTrialEnd) {
            $subscription->forceFill([
                'trial_ends_at' => $newTrialEnd,
                'current_period_end' => $newTrialEnd,
            ])->save();

            // Only the account's latest subscription decides the account status; an older one must not
            // turn an account with a newer paid plan back into a trial.
            $latestId = AccountSubscription::query()->where('account_id', $subscription->account_id)->latest()->value('id');
            if ($subscription->account && (int) $latestId === (int) $subscription->id) {
                $account = $subscription->account;
                // A trial that ended was suspended by the system; a new trial end lifts that. An admin
                // suspension or a cancellation stays.
                $trialEndedLock = $account->status === 'suspended' && ! $account->status_changed_by
                    && $account->status_reason === \App\Console\Commands\ExpireEndedTrials::REASON;
                $keepStatus = in_array($account->status, ['suspended', 'cancelled'], true) && ! $trialEndedLock;
                $account->forceFill([
                    'status' => $keepStatus ? $account->status : 'trialing',
                    'trial_ends_at' => $newTrialEnd,
                ] + ($trialEndedLock ? ['status_reason' => null, 'status_changed_at' => now()] : []))->save();
            }

            return $subscription->refresh();
        });
    }
}
