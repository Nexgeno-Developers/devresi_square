<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repair_issues') || ! Schema::hasColumn('repair_issues', 'final_contractor_id')) {
            return;
        }

        // New repairs are raised before a contractor is chosen.
        try {
            DB::statement('ALTER TABLE repair_issues MODIFY final_contractor_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $exception) {
            // Column may already be nullable on some environments.
            report($exception);
        }
    }

    public function down(): void
    {
        // Keep nullable — open repairs may have no contractor yet.
    }
};
