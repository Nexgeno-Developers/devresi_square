<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PrivacyDsarDeleteCommand extends Command
{
    protected $signature = 'privacy:dsar-delete
        {userId : Target user id}
        {--account= : Limit to one account id}
        {--dry-run : List actions without mutating}
        {--force : Required for live anonymisation}';

    protected $description = 'DSAR erasure stub: anonymise user PII and revoke portal/account logins (retains finance history).';

    public function handle(): int
    {
        $userId = (int) $this->argument('userId');
        $accountId = $this->option('account') !== null ? (int) $this->option('account') : null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $user = User::query()->find($userId);
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $actions = [
            "Anonymise users.id={$userId} name/email/phone",
            'Set users.status inactive / revoke login capability where columns exist',
            'Disable tenant_members.can_login for this user'.($accountId ? " (account {$accountId})" : ''),
            'Disable account_users.can_login for this user'.($accountId ? " (account {$accountId})" : ''),
            'Retain rent invoices / payments (no hard delete)',
        ];

        $this->info($dryRun ? 'DRY RUN — no changes will be written.' : 'LIVE DSAR anonymisation');
        foreach ($actions as $action) {
            $this->line(' - '.$action);
        }

        if ($dryRun) {
            Log::channel('single')->info('privacy.dsar.dry_run', [
                'user_id' => $userId,
                'account_id' => $accountId,
            ]);

            return self::SUCCESS;
        }

        if (! $force) {
            $this->error('Refusing live run without --force (use --dry-run first).');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $userId, $accountId) {
            $token = Str::lower(Str::random(10));
            $user->name = 'Deleted User '.$userId;
            $user->email = "deleted+{$userId}.{$token}@invalid.resisquare.local";
            if (Schema::hasColumn('users', 'phone')) {
                $user->phone = null;
            }
            if (Schema::hasColumn('users', 'status')) {
                $user->status = 0;
            }
            $user->save();

            if (Schema::hasTable('tenant_members') && Schema::hasColumn('tenant_members', 'can_login')) {
                $q = DB::table('tenant_members')->where('user_id', $userId);
                if ($accountId) {
                    $q->where('account_id', $accountId);
                }
                $q->update(['can_login' => 0, 'updated_at' => now()]);
            }

            if (Schema::hasTable('account_users') && Schema::hasColumn('account_users', 'can_login')) {
                $q = DB::table('account_users')->where('user_id', $userId);
                if ($accountId) {
                    $q->where('account_id', $accountId);
                }
                $q->update(['can_login' => 0, 'updated_at' => now()]);
            }
        });

        Log::channel('single')->info('privacy.dsar.completed', [
            'user_id' => $userId,
            'account_id' => $accountId,
        ]);

        $this->info('DSAR anonymisation completed.');

        return self::SUCCESS;
    }
}
