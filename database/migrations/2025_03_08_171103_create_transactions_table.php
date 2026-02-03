<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Dynamic FK references
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();

            // FKs
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');

            $table->string('transaction_number')->unique();
            $table->string('transaction_type')->nullable(); // Refund, Income, Expense, Adjustment, etc.
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories')->onDelete('set null'); // Salary, Advance Payment, Utility, etc.
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null');
            $table->foreignId('payer_id')->nullable()->constrained('users')->onDelete('set null'); // Tenant/Landlord/Company
            $table->foreignId('payee_id')->nullable()->constrained('users')->onDelete('set null'); // Contractor/Supplier

            $table->date('transaction_date');

            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            // $table->string('payment_method'); // Bank Transfer, Card, etc.
            $table->string('transaction_reference')->nullable();
            $table->decimal('credit', 10, 2)->nullable();
            $table->decimal('debit', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
