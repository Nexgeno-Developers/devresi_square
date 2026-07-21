<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_subscription_addons')) {
            return;
        }

        Schema::table('account_subscription_addons', function (Blueprint $table) {
            if (! Schema::hasColumn('account_subscription_addons', 'price_at_purchase_minor')) {
                $table->integer('price_at_purchase_minor')->nullable()->after('stripe_price_id');
            }

            if (! Schema::hasColumn('account_subscription_addons', 'addon_name_at_purchase')) {
                $table->string('addon_name_at_purchase')->nullable()->after('price_at_purchase_minor');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('account_subscription_addons')) {
            return;
        }

        Schema::table('account_subscription_addons', function (Blueprint $table) {
            if (Schema::hasColumn('account_subscription_addons', 'addon_name_at_purchase')) {
                $table->dropColumn('addon_name_at_purchase');
            }

            if (Schema::hasColumn('account_subscription_addons', 'price_at_purchase_minor')) {
                $table->dropColumn('price_at_purchase_minor');
            }
        });
    }
};
