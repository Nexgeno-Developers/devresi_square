<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('type', ['landlord', 'owner', 'freelancing_agent', 'contractor']);

            // Email verification
            $table->string('email_verification_token', 100)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Phone OTP
            $table->string('phone_otp', 10)->nullable();
            $table->timestamp('phone_otp_expires_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            // Status
            $table->enum('status', ['pending', 'email_verified', 'phone_verified', 'verified', 'approved', 'rejected'])
                  ->default('pending');

            // Admin actions
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Linked user after approval
            $table->unsignedBigInteger('user_id')->nullable();

            // Tracking
            $table->string('ip', 45)->nullable();
            $table->string('ref_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
