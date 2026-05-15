<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // 'email' or 'phone' — which channel the user chose to verify via
            $table->enum('verify_via', ['email', 'phone'])->nullable()->after('type');
            // unified OTP field (used for both email OTP and phone OTP)
            $table->string('otp_code', 10)->nullable()->after('verify_via');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['verify_via', 'otp_code', 'otp_expires_at', 'otp_verified_at']);
        });
    }
};
