<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_job_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('compliance_job_offers', 'compliance_record_id')) {
                $table->foreignId('compliance_record_id')
                    ->nullable()
                    ->after('completion_note')
                    ->constrained('compliance_records')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('compliance_job_offers', function (Blueprint $table) {
            if (Schema::hasColumn('compliance_job_offers', 'compliance_record_id')) {
                $table->dropConstrainedForeignId('compliance_record_id');
            }
        });
    }
};
