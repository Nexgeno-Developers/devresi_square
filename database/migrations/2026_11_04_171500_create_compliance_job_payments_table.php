<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One card payment per compliance invoice. The landlord pays Resisquare.
     * Staff later record the bank transfer of the contractor's share.
     */
    public function up(): void
    {
        if (Schema::hasTable('compliance_job_payments')) {
            return;
        }

        Schema::create('compliance_job_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_job_invoice_id')->unique()->constrained('compliance_job_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('commission_bearer', 16);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('landlord_amount', 10, 2);
            $table->decimal('contractor_amount', 10, 2);
            $table->string('currency', 3)->default('gbp');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_status', 16)->default('unpaid');
            $table->string('payout_reference', 64)->nullable();
            $table->timestamp('payout_paid_at')->nullable();
            $table->foreignId('payout_paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'payout_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_job_payments');
    }
};
