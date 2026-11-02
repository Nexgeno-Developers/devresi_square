<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('compliance_type_id')->constrained('compliance_types')->cascadeOnDelete();
            $table->string('trade', 32);
            $table->string('status', 24)->default('draft');
            $table->unsignedBigInteger('awarded_offer_id')->nullable()->index();
            $table->text('access_notes')->nullable();
            $table->timestamp('respond_by')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'property_id', 'compliance_type_id', 'status'], 'compliance_bookings_open_lookup');
        });

        Schema::create('compliance_job_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('compliance_booking_id')->constrained('compliance_bookings')->cascadeOnDelete();
            $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('invited');
            $table->string('pricing_type', 16)->nullable();
            $table->string('vat_treatment', 32)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 12, 2)->nullable();
            $table->decimal('vat_amount', 12, 2)->nullable();
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('call_out_fee', 12, 2)->nullable();
            $table->decimal('minimum_hours', 8, 2)->nullable();
            $table->decimal('price_cap', 12, 2)->nullable();
            $table->decimal('inspection_fee', 12, 2)->nullable();
            $table->string('materials', 16)->nullable();
            $table->decimal('materials_estimate', 12, 2)->nullable();
            $table->boolean('price_is_estimate')->default(false);
            $table->text('offer_note')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('decline_code', 24)->nullable();
            $table->text('withdraw_reason')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->json('visit_slots')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('offer_sent_at')->nullable();
            $table->timestamp('offer_expires_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
            $table->unique(['compliance_booking_id', 'contractor_user_id'], 'compliance_offers_booking_contractor');
            $table->index(['contractor_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_job_offers');
        Schema::dropIfExists('compliance_bookings');
    }
};
