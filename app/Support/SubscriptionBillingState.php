<?php

namespace App\Support;

use App\Console\Commands\ExpireEndedTrials;
use App\Models\Account;
use App\Models\AccountSubscription;

/**
 * Where an account stands with its trial and subscription, in the words the Super Admin screens show.
 * Read from the local records only; nothing here calls Stripe.
 */
class SubscriptionBillingState
{
    /** Summary groups shown on the accounts list: group key => [label, state keys]. */
    public const GROUPS = [
        'trial_no_card' => ['On trial, not purchased', ['trial_no_card']],
        'trial_card' => ['On trial, purchased', ['trial_card', 'trial_cancelling']],
        'trial_expired' => ['Trial expired, not purchased', ['trial_expired']],
        'subscribed' => ['Subscribed', ['subscribed', 'cancelling']],
        'payment_failed' => ['Payment failed', ['payment_failed']],
        'awaiting_stripe' => ['Waiting for Stripe', ['awaiting_stripe']],
        'ended' => ['Cancelled or expired', ['cancelled', 'expired']],
        'other' => ['Needs a look', ['suspended', 'active_unpaid', 'none']],
    ];

    /**
     * The subscription the admin screens describe: the live one, otherwise the latest.
     */
    public static function subscriptionFor(Account $account): ?AccountSubscription
    {
        return $account->currentSubscription ?? $account->latestSubscription;
    }

    /**
     * @return array{key: string, label: string, detail: string, tone: string}
     */
    public static function for(Account $account, ?AccountSubscription $subscription = null): array
    {
        $subscription ??= self::subscriptionFor($account);
        $trialEnd = $subscription?->trial_ends_at ?? $account->trial_ends_at;

        if ($account->status === 'suspended') {
            if (! $account->status_changed_by && $account->status_reason === ExpireEndedTrials::REASON) {
                return self::state('trial_expired', 'Trial expired, not purchased',
                    'Trial ended '.rs_date($trialEnd).'. No card was added.', 'danger');
            }

            return self::state('suspended', 'Suspended',
                $account->status_changed_by ? 'Suspended by a Resisquare admin.' : (string) $account->status_reason, 'warning');
        }

        if (! $subscription) {
            return self::state('none', 'No subscription', 'No plan is linked to this account.', 'secondary');
        }

        if ($account->status === 'cancelled' || in_array($subscription->status, ['cancelled', 'expired'], true)) {
            $key = $subscription->status === 'expired' ? 'expired' : 'cancelled';
            $when = $subscription->cancelled_at ? ' on '.rs_date($subscription->cancelled_at) : '';

            return self::state($key, $key === 'expired' ? 'Subscription expired' : 'Subscription cancelled',
                ($account->status_changed_by ? 'Closed by a Resisquare admin' : 'Ended').$when.'.', 'secondary');
        }

        $onStripe = filled($subscription->stripe_subscription_id);
        $price = self::price($subscription);

        if ($subscription->status === 'past_due' || $account->status === 'past_due') {
            return self::state('payment_failed', 'Payment failed',
                'The '.$price.' charge did not go through. Past due until it is paid or cancelled.', 'danger');
        }

        if ($subscription->status === 'trialing') {
            if ($trialEnd && $trialEnd->isPast()) {
                return $onStripe
                    ? self::state('awaiting_stripe', 'Trial over, waiting for Stripe',
                        'Card on file. The payment result since '.rs_date($trialEnd).' has not reached the app.', 'warning')
                    : self::state('trial_expired', 'Trial expired, not purchased',
                        'Trial ended '.rs_date($trialEnd).'. No card was added.', 'danger');
            }

            $ends = $trialEnd ? rs_date($trialEnd).' ('.self::daysLeft($trialEnd).')' : 'date not set';

            if (! $onStripe) {
                return self::state('trial_no_card', 'On trial, not purchased', 'No card yet. Trial ends '.$ends.'.', 'info');
            }

            return $subscription->cancel_at_period_end
                ? self::state('trial_cancelling', 'On trial, cancelled', 'Will not be charged. Trial ends '.$ends.'.', 'warning')
                : self::state('trial_card', 'On trial, purchased', 'Card saved. First charge '.$price.' on '.$ends.'.', 'primary');
        }

        if (! $onStripe) {
            return self::state('active_unpaid', 'Active, no payment on file', 'Marked active without a Stripe subscription.', 'warning');
        }

        $periodEnd = $subscription->current_period_end;

        if ($subscription->cancel_at_period_end) {
            return self::state('cancelling', 'Subscribed, cancelling', 'Access ends '.rs_date($periodEnd).'. No further charges.', 'warning');
        }

        if ($periodEnd && $periodEnd->isPast()) {
            return self::state('awaiting_stripe', 'Renewal due, waiting for Stripe',
                'The renewal of '.rs_date($periodEnd).' has not reached the app.', 'warning');
        }

        return self::state('subscribed', 'Subscribed', $price.'. Renews '.rs_date($periodEnd).'.', 'success');
    }

    /** "£29.00 a month", from the price agreed at signup, falling back to the plan. */
    public static function price(AccountSubscription $subscription): string
    {
        $annual = $subscription->billing_cycle === 'annual';
        $minor = $subscription->price_at_signup_minor
            ?? ($annual ? $subscription->plan?->annual_price_minor : $subscription->plan?->monthly_price_minor);
        $currency = strtoupper($subscription->currency_at_signup ?: ($subscription->plan?->currency ?: 'GBP'));

        return rs_money(((int) $minor) / 100, false, $currency).($annual ? ' a year' : ' a month');
    }

    private static function daysLeft(\Carbon\CarbonInterface $end): string
    {
        $days = (int) round(now()->startOfDay()->diffInDays($end->copy()->startOfDay()));

        return match (true) {
            $days <= 0 => 'today',
            $days === 1 => 'tomorrow',
            default => $days.' days left',
        };
    }

    private static function state(string $key, string $label, string $detail, string $tone): array
    {
        return compact('key', 'label', 'detail', 'tone');
    }
}
