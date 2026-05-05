<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->unsignedSmallInteger('reminder_days_before_due')->nullable()->after('due_date');
            $table->index('due_date', 'sys_sale_invoices_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->dropIndex('sys_sale_invoices_due_date_idx');

            $table->dropColumn([
                'reminder_days_before_due',
            ]);
        });
    }
};
