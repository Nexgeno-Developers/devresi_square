<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_invoice_headers', function (Blueprint $table) {
            $table->id();
            $table->string('header_name');
            $table->text('header_description')->nullable();
            $table->string('unique_reference_number', 100)->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_invoice_headers');
    }
};
