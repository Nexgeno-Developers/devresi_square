<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->foreignId('recurring_master_invoice_id')
                ->nullable()
                ->constrained('sys_sale_invoices')
                ->nullOnDelete();

            $table->unsignedInteger('recurring_sequence')
                ->nullable();

            $table->unsignedTinyInteger('recurring_month_interval')
                ->nullable(); // 1..12

            $table->unsignedInteger('recurring_custom_interval')
                ->nullable(); // e.g. 2, 3, 10

            $table->enum('recurring_custom_unit', ['day', 'week', 'month', 'year'])
                ->nullable();

            $table->boolean('unlimited_cycles')
                ->default(false);

            $table->unsignedInteger('recurring_cycles')
                ->nullable(); // total cycles including master (finite mode)

            $table->index(['recurring_master_invoice_id', 'recurring_sequence'], 'sys_sale_invoices_recurring_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->dropIndex('sys_sale_invoices_recurring_idx');
            $table->dropConstrainedForeignId('recurring_master_invoice_id');

            $table->dropColumn([
                'recurring_sequence',
                'recurring_month_interval',
                'recurring_custom_interval',
                'recurring_custom_unit',
                'unlimited_cycles',
                'recurring_cycles',
            ]);
        });
    }
};

