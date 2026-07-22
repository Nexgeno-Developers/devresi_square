<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('accounts')
            && ! Schema::hasColumn('accounts', 'subscription_activation_email_sent_at')
        ) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->timestamp('subscription_activation_email_sent_at')
                    ->nullable()
                    ->after('stripe_customer_id');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('accounts')
            && Schema::hasColumn('accounts', 'subscription_activation_email_sent_at')
        ) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('subscription_activation_email_sent_at');
            });
        }
    }
};
