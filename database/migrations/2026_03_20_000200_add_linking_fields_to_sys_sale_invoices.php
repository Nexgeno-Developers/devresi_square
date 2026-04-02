<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->enum('link_to_type', ['Property', 'Tenancy', 'Contractor'])
                ->nullable()
                ->after('invoice_header_id');
            $table->unsignedBigInteger('link_to_id')
                ->nullable()
                ->after('link_to_type');
            $table->enum('charge_to_type', ['Owner', 'Tenant', 'Contractor'])
                ->nullable()
                ->after('link_to_id');
            $table->unsignedBigInteger('charge_to_id')
                ->nullable()
                ->after('charge_to_type');
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('charge_to_id')
                ->constrained('bank_accounts')
                ->nullOnDelete();

            $table->index(['link_to_type', 'link_to_id'], 'sys_sale_invoices_link_to_idx');
            $table->index(['charge_to_type', 'charge_to_id'], 'sys_sale_invoices_charge_to_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sys_sale_invoices', function (Blueprint $table) {
            $table->dropIndex('sys_sale_invoices_link_to_idx');
            $table->dropIndex('sys_sale_invoices_charge_to_idx');
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn([
                'link_to_type',
                'link_to_id',
                'charge_to_type',
                'charge_to_id',
            ]);
        });
    }
};
