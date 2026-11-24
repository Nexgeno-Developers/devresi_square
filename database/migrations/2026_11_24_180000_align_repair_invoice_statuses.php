<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair invoices store status ids that the app treats as 1 Pending, 2 Paid, 3 Partially Paid, 4 Overdue,
 * 5 Cancelled (getInvoiceStatusDetails(), Transaction::updateInvoiceStatus()). The lookup table said
 * 3 Overdue, 4 Cancelled and had no 5, so the invoice PDF printed "OVERDUE" for a part-paid invoice and
 * failed for a cancelled one. This brings the table in line with the app.
 */
return new class extends Migration
{
    private const STATUSES = [
        1 => 'Pending',
        2 => 'Paid',
        3 => 'Partially Paid',
        4 => 'Overdue',
        5 => 'Cancelled',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('invoice_statuses')) {
            return;
        }

        foreach (self::STATUSES as $id => $name) {
            DB::table('invoice_statuses')->updateOrInsert(['id' => $id], ['name' => $name, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_statuses')) {
            return;
        }

        foreach ([3 => 'Overdue', 4 => 'Cancelled'] as $id => $name) {
            DB::table('invoice_statuses')->where('id', $id)->update(['name' => $name]);
        }
        DB::table('invoice_statuses')->where('id', 5)->delete();
    }
};
