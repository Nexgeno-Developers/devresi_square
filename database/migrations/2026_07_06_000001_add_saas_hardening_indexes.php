<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->indexes() as [$table, $columns, $name, $unique]) {
            $this->indexIfMissing($table, $columns, $name, $unique);
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive for production hardening indexes.
    }

    private function indexes(): array
    {
        return [
            ['accounts', ['owner_user_id'], 'accounts_owner_user_id_hidx', false],
            ['accounts', ['status'], 'accounts_status_hidx', false],
            ['account_users', ['user_id'], 'account_users_user_id_hidx', false],
            ['account_users', ['account_id', 'member_type'], 'account_users_account_member_hidx', false],
            ['properties', ['account_id'], 'properties_account_id_hidx', false],
            ['properties', ['account_id', 'property_identity_hash'], 'properties_account_identity_huniq', true],
            ['branches', ['account_id'], 'branches_account_id_hidx', false],
            ['staff', ['account_id'], 'staff_account_id_hidx', false],
            ['tenancies', ['account_id'], 'tenancies_account_id_hidx', false],
            ['tenant_members', ['account_id'], 'tenant_members_account_id_hidx', false],
            ['repair_issues', ['account_id'], 'repair_issues_account_id_hidx', false],
            ['work_orders', ['account_id'], 'work_orders_account_id_hidx', false],
            ['documents', ['account_id'], 'documents_account_id_hidx', false],
            ['notes', ['account_id'], 'notes_account_id_hidx', false],
            ['uploads', ['account_id'], 'uploads_account_id_hidx', false],
            ['events', ['account_id'], 'events_account_id_hidx', false],
            ['sys_sale_invoices', ['account_id'], 'sys_sale_invoices_account_hidx', false],
            ['sys_receipts', ['account_id'], 'sys_receipts_account_id_hidx', false],
            ['sys_payments', ['account_id'], 'sys_payments_account_id_hidx', false],
            ['gl_journals', ['account_id'], 'gl_journals_account_id_hidx', false],
            ['gl_journal_lines', ['account_id'], 'gl_journal_lines_account_hidx', false],
            ['property_participants', ['account_id', 'user_id'], 'property_participants_account_user_hidx', false],
            ['property_participants', ['property_id'], 'property_participants_property_hidx', false],
            ['property_participants', ['account_id', 'property_id', 'user_id', 'participant_type'], 'property_participants_access_huniq', true],
            ['account_subscriptions', ['account_id', 'status'], 'account_subscriptions_account_status_hidx', false],
            ['account_subscription_addons', ['account_id', 'status'], 'account_sub_addons_account_status_hidx', false],
        ];
    }

    private function indexIfMissing(string $table, array $columns, string $indexName, bool $unique): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasIndex($table, $indexName, $columns, $unique)) {
            return;
        }

        if ($unique && $this->hasDuplicateValues($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName, $unique) {
            $unique
                ? $blueprint->unique($columns, $indexName)
                : $blueprint->index($columns, $indexName);
        });
    }

    private function hasIndex(string $table, string $indexName, array $columns, bool $unique): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
        } catch (Throwable) {
            return false;
        }

        $expectedName = strtolower($indexName);
        $expectedColumns = array_map('strtolower', $columns);

        foreach ($indexes as $index) {
            if (strtolower((string) ($index['name'] ?? '')) === $expectedName) {
                return true;
            }

            $currentColumns = array_map('strtolower', $index['columns'] ?? []);
            $isUnique = (bool) ($index['unique'] ?? false);

            if ($currentColumns === $expectedColumns && (! $unique || $isUnique)) {
                return true;
            }
        }

        return false;
    }

    private function hasDuplicateValues(string $table, array $columns): bool
    {
        $query = DB::table($table)
            ->select($columns)
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->limit(1);

        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }

        return $query->exists();
    }
};
