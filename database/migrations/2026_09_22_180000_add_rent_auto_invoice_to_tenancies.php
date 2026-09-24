<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenancies', function (Blueprint $table) {
            if (! Schema::hasColumn('tenancies', 'rent_auto_invoice')) {
                $table->boolean('rent_auto_invoice')->default(false)->after('frequency');
            }
            if (! Schema::hasColumn('tenancies', 'rent_auto_invoice_tenant_user_id')) {
                $table->unsignedBigInteger('rent_auto_invoice_tenant_user_id')->nullable()->after('rent_auto_invoice');
            }
            if (! Schema::hasColumn('tenancies', 'rent_next_period_start')) {
                $table->date('rent_next_period_start')->nullable()->after('rent_auto_invoice_tenant_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenancies', function (Blueprint $table) {
            foreach (['rent_next_period_start', 'rent_auto_invoice_tenant_user_id', 'rent_auto_invoice'] as $column) {
                if (Schema::hasColumn('tenancies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
