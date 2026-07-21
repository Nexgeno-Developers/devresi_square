<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillSaasAccount extends Command
{
    protected $signature = 'saas:backfill-account
        {--owner_user_id= : Existing non-Super-Admin user id that owns the default SaaS account}
        {--account_type=estate_agent_company : landlord, estate_agent_freelance, or estate_agent_company}
        {--account_name=Default ResiSquare Account : Name for the default SaaS account}
        {--dry-run : Report rows that would be backfilled without writing changes}';

    protected $description = 'Create a default SaaS account for legacy data and backfill nullable account_id columns.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run only. No account, membership, or account_id values will be written.');

            foreach ($this->backfillTables() as $table) {
                $count = $this->countBackfillableRows($table);

                if ($count !== null) {
                    $this->line("{$table}: {$count} rows would be updated");
                }
            }

            return self::SUCCESS;
        }

        $ownerUserId = $this->option('owner_user_id') ?: $this->ask('Owner user id');
        $accountType = (string) $this->option('account_type');
        $accountName = trim((string) $this->option('account_name')) ?: 'Default ResiSquare Account';

        if (! in_array($accountType, ['landlord', 'estate_agent_freelance', 'estate_agent_company'], true)) {
            $this->error('Invalid account_type. Use landlord, estate_agent_freelance, or estate_agent_company.');

            return self::FAILURE;
        }

        if (! is_numeric($ownerUserId)) {
            $this->error('A valid --owner_user_id is required.');

            return self::FAILURE;
        }

        $owner = User::find((int) $ownerUserId);
        if (! $owner) {
            $this->error("Owner user {$ownerUserId} was not found.");

            return self::FAILURE;
        }

        if ($owner->isSuperAdmin()) {
            $this->error('Super Admin users must remain outside SaaS accounts. Choose a non-Super-Admin owner.');

            return self::FAILURE;
        }

        [$account, $counts] = DB::transaction(function () use ($owner, $accountType, $accountName) {
            $account = $this->resolveAccount($owner, $accountType, $accountName);

            AccountUser::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'user_id' => $owner->id,
                ],
                [
                    'member_type' => 'owner',
                    'access_level' => 'full',
                    'can_login' => true,
                    'status' => 'active',
                    'created_by' => $owner->id,
                ]
            );

            $counts = [];
            foreach ($this->backfillTables() as $table) {
                $affected = $this->backfillTable($table, $account->id);

                if ($affected !== null) {
                    $counts[$table] = $affected;
                }
            }

            return [$account, $counts];
        });

        $this->info("Using account #{$account->id}: {$account->account_name}");

        foreach ($counts as $table => $affected) {
            $this->line("{$table}: {$affected} rows updated");
        }

        $this->info('SaaS account backfill complete.');

        return self::SUCCESS;
    }

    private function resolveAccount(User $owner, string $accountType, string $accountName): Account
    {
        return Account::query()
            ->where('owner_user_id', $owner->id)
            ->where('account_name', $accountName)
            ->first()
            ?? Account::create([
                'owner_user_id' => $owner->id,
                'account_type' => $accountType,
                'account_name' => $accountName,
                'billing_email' => $owner->email,
                'billing_phone' => $owner->phone,
                'currency' => 'GBP',
                'status' => 'active',
                'trial_started_at' => null,
                'trial_ends_at' => null,
            ]);
    }

    private function backfillTable(string $table, int $accountId): ?int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'account_id')) {
            return null;
        }

        return DB::table($table)
            ->whereNull('account_id')
            ->update(['account_id' => $accountId]);
    }

    private function countBackfillableRows(string $table): ?int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'account_id')) {
            return null;
        }

        return DB::table($table)
            ->whereNull('account_id')
            ->count();
    }

    private function backfillTables(): array
    {
        return [
            'companies',
            'branches',
            'staff',
            'designations',
            'properties',
            'property_responsibilities',
            'tenancies',
            'tenant_members',
            'repair_issues',
            'work_orders',
            'documents',
            'notes',
            'uploads',
            'events',
            'registrations',
            'sys_sale_invoices',
            'sys_sale_invoice_items',
            'sys_receipts',
            'sys_payments',
            'gl_journals',
            'gl_journal_lines',
        ];
    }
}
