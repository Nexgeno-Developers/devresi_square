<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('tenancy_id');
            $table->unsignedBigInteger('tenant_user_id');
            $table->string('invoice_no', 32);
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->string('status', 20)->default('issued');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'invoice_no']);
            $table->index(['account_id', 'tenant_user_id']);
            $table->index(['account_id', 'tenancy_id']);
        });

        Schema::create('rent_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('rent_invoice_id');
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('method', 32);
            $table->string('reference', 120)->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'rent_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_payments');
        Schema::dropIfExists('rent_invoices');
    }
};
