<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compliance_job_invoices')) {
            return;
        }

        Schema::create('compliance_job_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_job_offer_id')->unique()->constrained('compliance_job_offers')->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('contractor_user_id')->constrained('users');
            $table->string('number', 32)->unique();
            $table->date('issued_on');
            $table->date('due_on');
            $table->date('supplied_on')->nullable();
            $table->json('lines');
            $table->decimal('net_amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2);
            $table->string('vat_number', 32)->nullable();
            $table->string('cis_status', 24)->nullable();
            $table->json('supplier')->nullable();
            $table->json('bill_to')->nullable();
            $table->json('site')->nullable();
            $table->json('payment')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('issued');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_job_invoices');
    }
};
