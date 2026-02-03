<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
