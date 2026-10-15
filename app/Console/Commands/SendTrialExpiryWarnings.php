<?php

namespace App\Console\Commands;

use App\Mail\MailManager;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrialExpiryWarnings extends Command
{
    protected $signature = 'accounts:trial-expiry-warning';

    protected $description = 'Email landlords whose Pro trial ends within two days.';

    public function handle(): int
    {
        $sent = 0;

        Account::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(2)])
            ->with('owner')
            ->orderBy('id')
            ->each(function (Account $account) use (&$sent): void {
                $recipient = $account->owner?->email ?: $account->billing_email;
                if (! $recipient || ! $account->trial_ends_at) {
                    return;
                }

                $key = 'trial-expiry-warning:'.$account->id.':'.$account->trial_ends_at->timestamp;
                if (! Cache::add($key, 1, now()->addDays(14))) {
                    return;
                }

                $name = e(trim((string) ($account->owner?->first_name ?: $account->owner?->name)) ?: 'there');
                $workspace = e($account->account_name ?: 'your workspace');
                $ends = e($account->trial_ends_at->timezone(config('app.timezone'))->format('j F Y'));
                $billing = e(route('backend.billing.index'));

                try {
                    Mail::to($recipient)->send(new MailManager([
                        'subject' => 'Your ResiSquare trial ends on '.$account->trial_ends_at->format('j F Y'),
                        'content' => '<p>Hi '.$name.',</p>'
                            .'<p>Your 7-day Pro trial for <strong>'.$workspace.'</strong> will end on '.$ends.'.</p>'
                            .'<p>To keep uninterrupted access to your portfolio, automated rent ledgers, compliance alerts, and team access, select a plan for your workspace.</p>'
                            .'<p><a href="'.$billing.'">Choose a Plan</a></p>'
                            .'<p>If you have any questions about choosing the right plan for your portfolio size, reply directly to this email.</p>'
                            .'<p>Best regards,<br>The ResiSquare Team</p>',
                        'attachments' => [],
                    ]));
                    $sent++;
                } catch (\Throwable $exception) {
                    Cache::forget($key);
                    Log::error('Trial expiry warning failed', [
                        'account_id' => $account->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        $this->info("Trial expiry warnings sent: {$sent}.");

        return self::SUCCESS;
    }
}
