<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Launch Step 8: bank_details must belong to a SaaS account (not only a user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_details')) {
            return;
        }

        if (! Schema::hasColumn('bank_details', 'account_id')) {
            Schema::table('bank_details', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('id');
                $table->index('account_id');
            });
        }

        // Prefer the owning account when the contact is a member of one workspace.
        if (Schema::hasTable('account_users')) {
            DB::statement('
                UPDATE bank_details bd
                INNER JOIN (
                    SELECT user_id, MIN(account_id) AS account_id
                    FROM account_users
                    WHERE status = \'active\'
                    GROUP BY user_id
                ) au ON au.user_id = bd.user_id
                SET bd.account_id = au.account_id
                WHERE bd.account_id IS NULL
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_details') && Schema::hasColumn('bank_details', 'account_id')) {
            Schema::table('bank_details', function (Blueprint $table) {
                $table->dropIndex(['account_id']);
                $table->dropColumn('account_id');
            });
        }
    }
};
