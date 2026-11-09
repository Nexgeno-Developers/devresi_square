<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_job_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('compliance_job_offers', 'job_status')) {
                $table->string('job_status', 24)->nullable()->after('status');
            }
            if (! Schema::hasColumn('compliance_job_offers', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('scheduled_for');
            }
            if (! Schema::hasColumn('compliance_job_offers', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('compliance_job_offers', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('compliance_job_offers', 'completion_note')) {
                $table->text('completion_note')->nullable()->after('confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compliance_job_offers', function (Blueprint $table) {
            foreach (['completion_note', 'confirmed_at', 'completed_at', 'started_at', 'job_status'] as $column) {
                if (Schema::hasColumn('compliance_job_offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
