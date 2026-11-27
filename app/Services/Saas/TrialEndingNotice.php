<?php

namespace App\Services\Saas;

use App\Jobs\SendNotificationJob;
use App\Models\Account;
use App\Models\NotificationLog;
use App\Support\SubscriptionBillingState;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * "Your free trial is ending" email and in-app notice, sent once per trial end.
 * Both are written to notification_logs, so a failed email is retried by notifications:retry
 * and shows in the account's delivery history.
 */
class TrialEndingNotice
{
    public const IDENTIFIER = 'account.trial_ending';

    /**
     * @return string sent | failed | already | no_recipient
     */
    public function send(Account $account): string
    {
        $account->loadMissing(['owner', 'currentSubscription.plan', 'latestSubscription.plan']);
        $owner = $account->owner;
        $recipient = $owner?->email ?: $account->billing_email;

        if (! $recipient || ! $account->trial_ends_at) {
            return 'no_recipient';
        }

        // A new end date (an extended trial) is a new notice; the same one is never repeated.
        $key = $account->id.'|'.$account->trial_ends_at->timestamp;

        // The cache key is how warnings sent before this class existed were remembered.
        if (NotificationLog::query()->where('idempotency_key', $this->key($key, 'email'))->exists()
            || Cache::has('trial-expiry-warning:'.$account->id.':'.$account->trial_ends_at->timestamp)) {
            return 'already';
        }

        $message = $this->message($account);
        $common = [
            'account_id' => $account->id,
            'identifier' => self::IDENTIFIER,
            'notifiable_type' => ($owner ?? $account)->getMorphClass(),
            'notifiable_id' => ($owner ?? $account)->getKey(),
            'subject_type' => $account->getMorphClass(),
            'subject_id' => $account->id,
            'payload' => [
                'action_url' => route('backend.billing.index'),
                'category' => 'system',
                'priority' => 'high',
                'trial_ends_at' => $account->trial_ends_at->toDateTimeString(),
            ],
            'status' => 'pending',
            'attempt' => 0,
            'max_attempts' => (int) config('notification_system.max_attempts', 3),
        ];

        try {
            $email = NotificationLog::create($common + [
                'channel' => 'email',
                'recipient' => $recipient,
                'subject' => $message['subject'],
                'message' => $message['email'],
                'idempotency_key' => $this->key($key, 'email'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return 'already';
        }

        if ($owner) {
            $bell = NotificationLog::create($common + [
                'channel' => 'system',
                'subject' => $message['title'],
                'message' => $message['bell'],
                'idempotency_key' => $this->key($key, 'system'),
            ]);
            $this->deliver($bell);
        }

        return $this->deliver($email) ? 'sent' : 'failed';
    }

    /**
     * @return array{subject: string, title: string, bell: string, email: string}
     */
    public function message(Account $account): array
    {
        $subscription = SubscriptionBillingState::subscriptionFor($account);
        $ends = $account->trial_ends_at->copy()->timezone(config('app.timezone'));
        $date = $ends->format('j F Y');
        $when = match (true) {
            $ends->isToday() => 'today',
            $ends->isTomorrow() => 'tomorrow',
            default => 'on '.$date,
        };
        $price = $subscription ? SubscriptionBillingState::price($subscription) : null;
        $cardSaved = filled($subscription?->stripe_subscription_id);
        $workspace = $account->account_name ?: 'your workspace';
        $title = 'Your free trial ends '.$when;

        if ($cardSaved && $subscription->cancel_at_period_end) {
            $lines = [
                'You cancelled your subscription, so your saved card will not be charged.',
                'Access to your workspace stops when the trial ends. You can subscribe again from Settings at any time.',
            ];
            $bell = 'You will not be charged. Access stops when the trial ends.';
            $button = 'Open Settings';
        } elseif ($cardSaved) {
            $lines = [
                'Your card is saved. When the trial ends we will charge '.$price.' to it automatically, and again each billing period until you cancel.',
                'To avoid the charge, cancel before the trial ends: Settings, then Manage billing.',
                'If the payment does not go through, your account is marked past due. Update your card in Manage billing to put it right.',
            ];
            $bell = 'Your saved card will be charged '.$price.' when the trial ends.';
            $button = 'Manage billing';
        } else {
            $lines = [
                'No card is saved yet. Add one before the trial ends to keep using your workspace: Settings, then Activate subscription.'.($price ? ' The plan is '.$price.'.' : ''),
                'Adding your card now does not charge you before the trial is over.',
                'If you do not, access pauses when the trial ends. Your data is kept and you can subscribe later.',
            ];
            $bell = 'Add a card before the trial ends to keep access.';
            $button = 'Activate subscription';
        }

        $name = e(trim((string) ($account->owner?->first_name ?: $account->owner?->name)) ?: 'there');
        $email = '<p>Hi '.$name.',</p>'
            .'<p>The free trial for <strong>'.e($workspace).'</strong> ends <strong>'.e($when).'</strong> ('.e($date).').</p>'
            .implode('', array_map(fn (string $line) => '<p>'.e($line).'</p>', $lines))
            .'<p><a href="'.e(route('backend.billing.index')).'">'.e($button).'</a></p>'
            .'<p>Best regards,<br>The ResiSquare Team</p>';

        return [
            'subject' => 'Your ResiSquare free trial ends '.$when.' ('.$date.')',
            'title' => $title,
            'bell' => $bell,
            'email' => $email,
        ];
    }

    private function deliver(NotificationLog $log): bool
    {
        try {
            // Run inline, not through dispatchSync: a first failure there would already tell the
            // landlord it "could not be delivered after all attempts" while retries remain.
            (new SendNotificationJob($log))->handle();

            return $log->fresh()?->status === 'sent';
        } catch (\Throwable $exception) {
            // The log row stays "failed", and notifications:retry tries it again.
            Log::error('Trial ending notice failed', [
                'account_id' => $log->account_id,
                'channel' => $log->channel,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function key(string $trial, string $channel): string
    {
        return hash('sha256', self::IDENTIFIER.'|'.$trial.'|'.$channel);
    }
}
