<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\Saas\TrialEndingNotice;
use Illuminate\Console\Command;

/**
 * Runs once a day. Everyone whose free trial ends tomorrow (or later today, if an earlier run
 * missed them) gets one email and one in-app notice.
 */
class SendTrialExpiryWarnings extends Command
{
    protected $signature = 'accounts:trial-expiry-warning
        {--dry-run : List the accounts without sending anything}
        {--account=* : Only check these account ids}';

    protected $description = 'Email and notify landlords whose free trial ends tomorrow.';

    public function handle(TrialEndingNotice $notice): int
    {
        $counts = ['sent' => 0, 'failed' => 0, 'already' => 0, 'no_recipient' => 0];
        $dryRun = (bool) $this->option('dry-run');
        $found = 0;

        Account::query()
            ->where('status', 'trialing')
            ->when($this->option('account'), fn ($query, $ids) => $query->whereIn('id', array_map('intval', $ids)))
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDay()->endOfDay()])
            ->with(['owner', 'currentSubscription.plan', 'latestSubscription.plan'])
            ->orderBy('id')
            ->each(function (Account $account) use ($notice, $dryRun, &$counts, &$found): void {
                $found++;
                if ($dryRun) {
                    $this->line($account->id.' '.$account->account_name.' (trial ends '.$account->trial_ends_at->format('j M Y H:i').')');

                    return;
                }

                $counts[$notice->send($account)]++;
            });

        if ($dryRun) {
            $this->info("{$found} ".str('account')->plural($found).' would be told.');

            return self::SUCCESS;
        }

        $this->info("Trial expiry warnings sent: {$counts['sent']}. Already told: {$counts['already']}. Failed: {$counts['failed']}.");

        return self::SUCCESS;
    }
}
