<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contractor_profiles')) {
            Schema::table('contractor_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('contractor_profiles', 'bank_account_name')) {
                    $table->string('bank_account_name')->nullable();
                }
                if (! Schema::hasColumn('contractor_profiles', 'bank_sort_code')) {
                    $table->string('bank_sort_code', 8)->nullable();
                }
                if (! Schema::hasColumn('contractor_profiles', 'bank_account_number')) {
                    $table->string('bank_account_number', 8)->nullable();
                }
                if (! Schema::hasColumn('contractor_profiles', 'bank_name')) {
                    $table->string('bank_name')->nullable();
                }
            });
        }

        if (Schema::hasTable('repair_job_invoices')) {
            Schema::table('repair_job_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('repair_job_invoices', 'supplier')) {
                    $table->json('supplier')->nullable();
                }
                if (! Schema::hasColumn('repair_job_invoices', 'bill_to')) {
                    $table->json('bill_to')->nullable();
                }
                if (! Schema::hasColumn('repair_job_invoices', 'site')) {
                    $table->json('site')->nullable();
                }
                if (! Schema::hasColumn('repair_job_invoices', 'payment')) {
                    $table->json('payment')->nullable();
                }
                if (! Schema::hasColumn('repair_job_invoices', 'supplied_on')) {
                    $table->date('supplied_on')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contractor_profiles')) {
            Schema::table('contractor_profiles', function (Blueprint $table) {
                foreach (['bank_name', 'bank_account_number', 'bank_sort_code', 'bank_account_name'] as $column) {
                    if (Schema::hasColumn('contractor_profiles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('repair_job_invoices')) {
            Schema::table('repair_job_invoices', function (Blueprint $table) {
                foreach (['supplied_on', 'payment', 'site', 'bill_to', 'supplier'] as $column) {
                    if (Schema::hasColumn('repair_job_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
