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
            && ! Schema::hasColumn('accounts', 'registration_welcome_email_sent_at')
        ) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->timestamp('registration_welcome_email_sent_at')
                    ->nullable()
                    ->after('stripe_customer_id');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('accounts')
            && Schema::hasColumn('accounts', 'registration_welcome_email_sent_at')
        ) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('registration_welcome_email_sent_at');
            });
        }
    }
};
