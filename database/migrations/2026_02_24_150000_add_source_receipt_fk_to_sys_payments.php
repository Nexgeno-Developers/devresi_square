<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sys_payments') || !Schema::hasTable('sys_receipts')) {
            return;
        }

        if (!Schema::hasColumn('sys_payments', 'source_receipt_id')) {
            return;
        }

        $orphans = DB::table('sys_payments as p')
            ->leftJoin('sys_receipts as r', 'p.source_receipt_id', '=', 'r.id')
            ->whereNotNull('p.source_receipt_id')
            ->whereNull('r.id')
            ->count();

        if ($orphans > 0) {
            throw new \RuntimeException("Cannot add FK: {$orphans} sys_payments rows reference missing receipts. Clean them before rerunning this migration.");
        }

        Schema::table('sys_payments', function (Blueprint $table) {
            $table->foreign('source_receipt_id')
                ->references('id')
                ->on('sys_receipts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sys_payments')) {
            return;
        }

        if (Schema::hasColumn('sys_payments', 'source_receipt_id')) {
            Schema::table('sys_payments', function (Blueprint $table) {
                $table->dropForeign(['source_receipt_id']);
            });
        }
    }
};
