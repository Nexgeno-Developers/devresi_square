<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Support\AuditTrail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Trials created at sign-up live only in the app until the owner checks out, so nothing in Stripe ends them.
 * This suspends an account whose trial is over and that has no live Stripe subscription. It is a system
 * suspension (status_changed_by stays empty), so paying through Billing & Plan unlocks the account again.
 * The local subscription stays "trialing" on purpose: checkout needs it, and it carries no trial time over.
 */
class ExpireEndedTrials extends Command
{
    public const REASON = 'Trial ended';

    protected $signature = 'accounts:expire-trials
        {--dry-run : List the accounts without changing them}
        {--account=* : Only check these account ids}';

    protected $description = 'Suspend accounts whose free trial has ended without a paid plan.';

    public function handle(): int
    {
        $count = 0;

        Account::query()
            ->where('status', 'trialing')
            ->when($this->option('account'), fn ($query, $ids) => $query->whereIn('id', array_map('intval', $ids)))
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            // Stripe ends its own trials (and its webhooks update the account), so leave those alone.
            ->whereDoesntHave('subscriptions', fn ($query) => $query
                ->whereNotNull('stripe_subscription_id')
                ->whereIn('status', ['trialing', 'active', 'past_due']))
            ->orderBy('id')
            ->each(function (Account $account) use (&$count): void {
                $count++;
                if ($this->option('dry-run')) {
                    $this->line($account->id.' '.$account->account_name.' (trial ended '.$account->trial_ends_at->toDateString().')');

                    return;
                }

                $before = $account->only(['status', 'status_reason']);
                $account->forceFill([
                    'status' => 'suspended',
                    'status_reason' => self::REASON,
                    'status_changed_at' => now(),
                    'status_changed_by' => null,
                ])->save();
                AuditTrail::record($account->id, null, 'account_trial_expired', $account, $before, $account->only(['status', 'status_reason']));
            });

        $verb = $this->option('dry-run') ? 'would be suspended' : 'suspended';
        $this->info($count.' ended '.str('trial')->plural($count).' '.$verb.'.');
        if ($count > 0 && ! $this->option('dry-run')) {
            Log::info('Ended trials suspended', ['count' => $count]);
        }

        return self::SUCCESS;
    }
}
