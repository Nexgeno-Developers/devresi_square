<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('compliance_records', 'served_to_tenant_at')) {
                $table->timestamp('served_to_tenant_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('compliance_records', 'served_notes')) {
                $table->string('served_notes', 1000)->nullable()->after('served_to_tenant_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            foreach (['served_notes', 'served_to_tenant_at'] as $column) {
                if (Schema::hasColumn('compliance_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
