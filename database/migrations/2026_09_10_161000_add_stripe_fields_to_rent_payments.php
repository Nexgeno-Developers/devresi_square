<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->decimal('fee_amount', 12, 2)->default(0)->after('amount');
            $table->string('stripe_checkout_session_id', 255)->nullable()->after('reference');
            $table->string('stripe_payment_intent_id', 255)->nullable()->after('stripe_checkout_session_id');
            $table->unique('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn([
                'fee_amount',
                'stripe_checkout_session_id',
                'stripe_payment_intent_id',
            ]);
        });
    }
};
