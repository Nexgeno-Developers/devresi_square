<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->string('period', 7); // YYYY-MM
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['gl_account_id','period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_account_balances');
    }
};

