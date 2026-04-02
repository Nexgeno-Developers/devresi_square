<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameColumnIfExists('sys_payments', 'bank_account_id', 'sys_bank_account_id');
        $this->renameColumnIfExists('sys_refunds', 'bank_account_id', 'sys_bank_account_id');

        if (Schema::hasTable('sys_receipts')) {
            // Drop FK on old column name first (if any), then rename, then add new FK.
            $this->dropForeignIfExists('sys_receipts', 'bank_account_id');
            $this->renameColumnIfExists('sys_receipts', 'bank_account_id', 'sys_bank_account_id');

            if (Schema::hasColumn('sys_receipts', 'sys_bank_account_id') && Schema::hasTable('sys_bank_accounts')) {
                DB::statement("
                    ALTER TABLE `sys_receipts`
                    ADD CONSTRAINT `sys_receipts_sys_bank_account_id_foreign`
                    FOREIGN KEY (`sys_bank_account_id`) REFERENCES `sys_bank_accounts`(`id`)
                    ON DELETE RESTRICT
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sys_receipts')) {
            $this->dropForeignIfExists('sys_receipts', 'sys_bank_account_id');
            $this->renameColumnIfExists('sys_receipts', 'sys_bank_account_id', 'bank_account_id');

            if (Schema::hasColumn('sys_receipts', 'bank_account_id') && Schema::hasTable('sys_bank_accounts')) {
                DB::statement("
                    ALTER TABLE `sys_receipts`
                    ADD CONSTRAINT `sys_receipts_bank_account_id_foreign`
                    FOREIGN KEY (`bank_account_id`) REFERENCES `sys_bank_accounts`(`id`)
                    ON DELETE RESTRICT
                ");
            }
        }

        $this->renameColumnIfExists('sys_refunds', 'sys_bank_account_id', 'bank_account_id');
        $this->renameColumnIfExists('sys_payments', 'sys_bank_account_id', 'bank_account_id');
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` CHANGE COLUMN `{$from}` `{$to}` BIGINT UNSIGNED NOT NULL");
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $foreignKeyName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreignKeyName) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKeyName}`");
        }
    }
};
