<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('sys_receipts', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('notes');
            }
        });

        Schema::table('sys_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('sys_payments', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sys_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('sys_receipts', 'payment_meta')) {
                $table->dropColumn('payment_meta');
            }
        });

        Schema::table('sys_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sys_payments', 'payment_meta')) {
                $table->dropColumn('payment_meta');
            }
        });
    }
};
