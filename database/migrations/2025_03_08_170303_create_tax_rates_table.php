<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Example: Standard VAT, Reduced VAT
            $table->decimal('rate', 5, 2); // Example: 20.00% for UK VAT
            $table->timestamps();
        });

        // Insert UK VAT Rates
        DB::table('tax_rates')->insert([
            ['name' => 'Standard VAT', 'rate' => 20.00],
            ['name' => 'Reduced VAT', 'rate' => 5.00],
            ['name' => 'Zero VAT', 'rate' => 0.00],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
