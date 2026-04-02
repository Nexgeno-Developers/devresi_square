<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sys_bank_accounts') || !Schema::hasColumn('sys_bank_accounts', 'user_id')) {
            return;
        }

        Schema::table('sys_bank_accounts', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
                // no-op: foreign key may not exist
            }

            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sys_bank_accounts') || Schema::hasColumn('sys_bank_accounts', 'user_id')) {
            return;
        }

        Schema::table('sys_bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }
};
