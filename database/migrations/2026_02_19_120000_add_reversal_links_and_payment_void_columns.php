<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gl_journals') && !Schema::hasColumn('gl_journals', 'reversal_of_id')) {
            Schema::table('gl_journals', function (Blueprint $table) {
                $table->unsignedBigInteger('reversal_of_id')->nullable()->after('id');
                $table->index('reversal_of_id', 'gl_journals_reversal_of_id_idx');
            });
        }

        if (Schema::hasTable('sys_payments')) {
            Schema::table('sys_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('sys_payments', 'is_voided')) {
                    $table->boolean('is_voided')->default(false)->after('gl_journal_id');
                }
                if (!Schema::hasColumn('sys_payments', 'voided_at')) {
                    $table->timestamp('voided_at')->nullable()->after('is_voided');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gl_journals') && Schema::hasColumn('gl_journals', 'reversal_of_id')) {
            Schema::table('gl_journals', function (Blueprint $table) {
                $table->dropIndex('gl_journals_reversal_of_id_idx');
                $table->dropColumn('reversal_of_id');
            });
        }

        if (Schema::hasTable('sys_payments')) {
            Schema::table('sys_payments', function (Blueprint $table) {
                if (Schema::hasColumn('sys_payments', 'voided_at')) {
                    $table->dropColumn('voided_at');
                }
                if (Schema::hasColumn('sys_payments', 'is_voided')) {
                    $table->dropColumn('is_voided');
                }
            });
        }
    }
};
