<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gl_journal_lines')) {
            Schema::table('gl_journal_lines', function (Blueprint $table) {
                $table->index(['company_id', 'user_id', 'gl_account_id', 'created_at'], 'gl_journal_lines_comp_user_acc_created_idx');
            });
        }

        if (Schema::hasTable('gl_journals')) {
            Schema::table('gl_journals', function (Blueprint $table) {
                $table->index('date', 'gl_journals_date_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gl_journal_lines')) {
            Schema::table('gl_journal_lines', function (Blueprint $table) {
                $table->dropIndex('gl_journal_lines_comp_user_acc_created_idx');
            });
        }

        if (Schema::hasTable('gl_journals')) {
            Schema::table('gl_journals', function (Blueprint $table) {
                $table->dropIndex('gl_journals_date_idx');
            });
        }
    }
};
