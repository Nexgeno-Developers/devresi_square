<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sys_receipts') || !Schema::hasTable('sys_bank_accounts')) {
            return;
        }

        // Backfill referenced IDs from legacy bank_accounts into sys_bank_accounts.
        if (Schema::hasTable('bank_accounts')) {
            DB::statement("
                INSERT INTO sys_bank_accounts (id, account_name, account_no, sort_code, bank_name, swift_code, is_active, is_primary, branch, ifsc_code, account_type, purpose, opening_balance, balance_type, created_at, updated_at)
                SELECT b.id, b.account_name, b.account_no, b.sort_code, b.bank_name, b.swift_code, b.is_active, b.is_primary, b.branch, b.ifsc_code, b.account_type, b.purpose, b.opening_balance, b.balance_type, COALESCE(b.created_at, NOW()), COALESCE(b.updated_at, NOW())
                FROM bank_accounts b
                INNER JOIN (SELECT DISTINCT bank_account_id FROM sys_receipts) r ON r.bank_account_id = b.id
                LEFT JOIN sys_bank_accounts s ON s.id = b.id
                WHERE s.id IS NULL
            ");
        }

        $this->dropForeignIfExists('sys_receipts', 'bank_account_id');

        $orphanCount = DB::table('sys_receipts as r')
            ->leftJoin('sys_bank_accounts as s', 's.id', '=', 'r.bank_account_id')
            ->whereNull('s.id')
            ->count();

        if ($orphanCount > 0) {
            throw new \RuntimeException("Cannot add FK: {$orphanCount} sys_receipts rows reference missing sys_bank_accounts IDs.");
        }

        Schema::table('sys_receipts', function (Blueprint $table) {
            $table->foreign('bank_account_id')
                ->references('id')
                ->on('sys_bank_accounts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sys_receipts') || !Schema::hasTable('bank_accounts')) {
            return;
        }

        $this->dropForeignIfExists('sys_receipts', 'bank_account_id');

        Schema::table('sys_receipts', function (Blueprint $table) {
            $table->foreign('bank_account_id')
                ->references('id')
                ->on('bank_accounts')
                ->onDelete('restrict');
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
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
