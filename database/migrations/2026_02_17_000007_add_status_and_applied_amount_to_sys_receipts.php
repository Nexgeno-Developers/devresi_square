<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sys_receipts')) {

            Schema::table('sys_receipts', function (Blueprint $table) {

                if (!Schema::hasColumn('sys_receipts', 'status')) {
                    $table->enum('status', [
                        'unapplied',
                        'partially_applied',
                        'applied'
                    ])->default('unapplied')->after('notes');
                }

                if (!Schema::hasColumn('sys_receipts', 'applied_amount')) {
                    $table->decimal('applied_amount', 15, 2)
                          ->default(0)
                          ->after('status');
                }
            });

            // Backfill existing records so legacy receipts are not treated as new credit
            DB::table('sys_receipts')
                ->update(['status' => 'applied']);

            DB::table('sys_receipts')
                ->update(['applied_amount' => DB::raw('amount')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sys_receipts')) {

            Schema::table('sys_receipts', function (Blueprint $table) {

                if (Schema::hasColumn('sys_receipts', 'applied_amount')) {
                    $table->dropColumn('applied_amount');
                }

                if (Schema::hasColumn('sys_receipts', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
