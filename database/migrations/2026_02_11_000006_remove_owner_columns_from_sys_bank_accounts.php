<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sys_bank_accounts')) {
            return;
        }

        $hasOwnerType = Schema::hasColumn('sys_bank_accounts', 'owner_type');
        $hasOwnerId = Schema::hasColumn('sys_bank_accounts', 'owner_id');

        if (!$hasOwnerType && !$hasOwnerId) {
            return;
        }

        Schema::table('sys_bank_accounts', function (Blueprint $table) use ($hasOwnerType, $hasOwnerId) {
            if ($hasOwnerType && $hasOwnerId) {
                $table->dropIndex(['owner_type', 'owner_id']);
            }

            if ($hasOwnerType) {
                $table->dropColumn('owner_type');
            }

            if ($hasOwnerId) {
                $table->dropColumn('owner_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sys_bank_accounts')) {
            return;
        }

        Schema::table('sys_bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('sys_bank_accounts', 'owner_type')) {
                $table->string('owner_type')->nullable();
            }

            if (!Schema::hasColumn('sys_bank_accounts', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable();
            }
        });

        if (Schema::hasColumn('sys_bank_accounts', 'owner_type') && Schema::hasColumn('sys_bank_accounts', 'owner_id')) {
            Schema::table('sys_bank_accounts', function (Blueprint $table) {
                $table->index(['owner_type', 'owner_id']);
            });
        }
    }
};
