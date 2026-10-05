<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_invoices', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id', 120)->nullable()->after('note');
            $table->index('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('rent_invoices', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
