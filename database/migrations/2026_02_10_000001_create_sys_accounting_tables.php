<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('invoice_no', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('balance_amount', 15, 2);
            $table->enum('status', ['draft', 'issued', 'paid', 'partial', 'cancelled'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('invoice_no', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('balance_amount', 15, 2);
            $table->enum('status', ['draft', 'received', 'paid', 'partial', 'cancelled'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_adjustment_notes', function (Blueprint $table) {
            $table->id();
            $table->enum('note_type', ['credit', 'debit']);
            $table->enum('adjustment_reason', ['return', 'refund', 'writeoff'])->nullable();
            $table->enum('reference_type', ['sale_invoice', 'purchase_invoice']);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('note_no', 50)->unique();
            $table->date('note_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('balance_amount', 15, 2);
            $table->boolean('is_refunded')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_sale_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_invoice_id')->constrained('sys_sale_invoices');
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained('sys_taxes');
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('sys_purchase_invoices');
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained('sys_taxes');
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('bank_account_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->enum('payment_type', ['income', 'expense', 'general']);
            $table->enum('reference_type', ['sale_invoice', 'purchase_invoice'])->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_note_id')->constrained('sys_adjustment_notes');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('bank_account_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->date('refund_date');
            $table->decimal('amount', 15, 2);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sys_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('receiptable_type', 100);
            $table->unsignedBigInteger('receiptable_id');
            $table->string('receipt_no', 50)->unique();
            $table->date('receipt_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['receiptable_type', 'receiptable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_receipts');
        Schema::dropIfExists('sys_refunds');
        Schema::dropIfExists('sys_payments');
        Schema::dropIfExists('sys_purchase_invoice_items');
        Schema::dropIfExists('sys_sale_invoice_items');
        Schema::dropIfExists('sys_adjustment_notes');
        Schema::dropIfExists('sys_purchase_invoices');
        Schema::dropIfExists('sys_sale_invoices');
        Schema::dropIfExists('sys_expense_categories');
        Schema::dropIfExists('sys_income_categories');
        Schema::dropIfExists('sys_taxes');
    }
};
