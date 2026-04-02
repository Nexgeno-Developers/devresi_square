<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sys_bank_accounts') && !Schema::hasColumn('sys_bank_accounts', 'gl_account_id')) {
            Schema::table('sys_bank_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('gl_account_id')->nullable()->after('balance_type');
                $table->foreign('gl_account_id')->references('id')->on('gl_accounts');
                $table->index('gl_account_id', 'sys_bank_accounts_gl_account_id_idx');
            });

            // Optional backfill: map all existing bank accounts to cash/bank GL (code 1100) if present
            $cashId = DB::table('gl_accounts')->where('code', '1100')->value('id');
            if ($cashId) {
                DB::table('sys_bank_accounts')->update(['gl_account_id' => $cashId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sys_bank_accounts') && Schema::hasColumn('sys_bank_accounts', 'gl_account_id')) {
            Schema::table('sys_bank_accounts', function (Blueprint $table) {
                $table->dropForeign(['gl_account_id']);
                $table->dropIndex('sys_bank_accounts_gl_account_id_idx');
                $table->dropColumn('gl_account_id');
            });
        }
    }
};
