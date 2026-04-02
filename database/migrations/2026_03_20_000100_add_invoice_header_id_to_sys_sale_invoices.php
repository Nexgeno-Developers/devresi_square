<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->foreignId('invoice_header_id')
                ->nullable()
                ->after('user_id')
                ->constrained('sys_invoice_headers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_header_id');
        });
    }
};
