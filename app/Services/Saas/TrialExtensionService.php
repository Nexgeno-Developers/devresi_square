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

            if ($subscription->account) {
                $subscription->account->forceFill([
                    'status' => 'trialing',
                    'trial_ends_at' => $newTrialEnd,
                ])->save();
            }

            return $subscription->refresh();
        });
    }
}
