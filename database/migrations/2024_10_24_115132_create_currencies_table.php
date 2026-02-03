<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id(); // Primary key (auto-incrementing)
            $table->string('name'); // Full name of the currency (e.g., British Pound)
            $table->string('code', 3)->unique(); // ISO currency code (e.g., GBP)
            $table->string('symbol'); // Currency symbol (e.g., £)
            $table->enum('symbol_position', ['before', 'after'])->default('before'); // Position of the symbol (before/after the value)
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // Exchange rate relative to the base currency (default 1 for GBP)
            $table->boolean('is_default')->default(false); // Flag for default currency
            $table->boolean('active')->default(true); // Whether the currency is active
            $table->timestamps(); // created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
