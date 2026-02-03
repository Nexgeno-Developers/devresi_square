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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade'); // Work Order Reference
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('quantity')->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onDelete('set null');
            $table->decimal('tax_rate', 5, 2)->default(0)->nullable(); // Tax Rate Percentage
            $table->decimal('total_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
