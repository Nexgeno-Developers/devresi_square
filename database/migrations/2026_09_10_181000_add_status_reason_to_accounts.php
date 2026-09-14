<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'status_reason')) {
                $table->text('status_reason')->nullable();
            }
            if (! Schema::hasColumn('accounts', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable();
            }
            if (! Schema::hasColumn('accounts', 'status_changed_by')) {
                $table->unsignedBigInteger('status_changed_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            foreach (['status_reason', 'status_changed_at', 'status_changed_by'] as $column) {
                if (Schema::hasColumn('accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
