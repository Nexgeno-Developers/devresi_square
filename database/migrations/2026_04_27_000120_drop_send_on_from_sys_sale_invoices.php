<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sys_sale_invoices')) {
            return;
        }

        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sys_sale_invoices', 'send_on')) {
                $table->dropColumn('send_on');
            }
            if (Schema::hasColumn('sys_sale_invoices', 'send_days_before_due')) {
                $table->dropColumn('send_days_before_due');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sys_sale_invoices')) {
            return;
        }

        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sys_sale_invoices', 'send_on')) {
                $table->date('send_on')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('sys_sale_invoices', 'send_days_before_due')) {
                $table->unsignedSmallInteger('send_days_before_due')->nullable()->after('send_on');
            }
        });
    }
};

