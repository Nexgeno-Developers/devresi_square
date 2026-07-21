<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_subscriptions')) {
            return;
        }

        Schema::table('account_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('account_subscriptions', 'price_at_signup_minor')) {
                $table->integer('price_at_signup_minor')->nullable()->after('stripe_price_id');
            }

            if (! Schema::hasColumn('account_subscriptions', 'currency_at_signup')) {
                $table->string('currency_at_signup')->default('GBP')->after('price_at_signup_minor');
            }

            if (! Schema::hasColumn('account_subscriptions', 'plan_name_at_signup')) {
                $table->string('plan_name_at_signup')->nullable()->after('currency_at_signup');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive for production compatibility.
    }
};
