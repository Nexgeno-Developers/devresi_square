<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_issue_contractor_assignments', function (Blueprint $table) {
            $table->string('quote_token', 80)->nullable()->unique()->after('status');
            $table->timestamp('quote_requested_at')->nullable()->after('quote_token');
            $table->timestamp('quote_submitted_at')->nullable()->after('quote_requested_at');
            $table->json('contractor_availability_options')->nullable()->after('contractor_preferred_availability');
            $table->string('consultant_name')->nullable()->after('contractor_availability_options');
            $table->string('consultant_phone')->nullable()->after('consultant_name');
            $table->date('tentative_start_date')->nullable()->after('consultant_phone');
            $table->date('tentative_end_date')->nullable()->after('tentative_start_date');
            $table->text('quote_notes')->nullable()->after('tentative_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('repair_issue_contractor_assignments', function (Blueprint $table) {
            $table->dropUnique(['quote_token']);
            $table->dropColumn([
                'quote_token',
                'quote_requested_at',
                'quote_submitted_at',
                'contractor_availability_options',
                'consultant_name',
                'consultant_phone',
                'tentative_start_date',
                'tentative_end_date',
                'quote_notes',
            ]);
        });
    }
};
