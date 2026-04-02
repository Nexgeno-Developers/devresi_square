<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sys_bank_accounts') && !Schema::hasColumn('sys_bank_accounts', 'gl_account_id')) {
            Schema::table('sys_bank_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('gl_account_id')->nullable()->after('balance_type');
            });
        }
        if (Schema::hasTable('sys_payments') && !Schema::hasColumn('sys_payments', 'gl_journal_id')) {
            Schema::table('sys_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('gl_journal_id')->nullable()->after('notes');
                $table->unsignedBigInteger('source_receipt_id')->nullable()->after('gl_journal_id');
            });
        }
        if (Schema::hasTable('sys_receipts') && !Schema::hasColumn('sys_receipts', 'gl_journal_id')) {
            Schema::table('sys_receipts', function (Blueprint $table) {
                $table->unsignedBigInteger('gl_journal_id')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sys_bank_accounts') && Schema::hasColumn('sys_bank_accounts', 'gl_account_id')) {
            Schema::table('sys_bank_accounts', function (Blueprint $table) {
                $table->dropColumn('gl_account_id');
            });
        }
        if (Schema::hasTable('sys_payments') && Schema::hasColumn('sys_payments', 'gl_journal_id')) {
            Schema::table('sys_payments', function (Blueprint $table) {
                $table->dropColumn(['gl_journal_id','source_receipt_id']);
            });
        }
        if (Schema::hasTable('sys_receipts') && Schema::hasColumn('sys_receipts', 'gl_journal_id')) {
            Schema::table('sys_receipts', function (Blueprint $table) {
                $table->dropColumn('gl_journal_id');
            });
        }
    }
};

