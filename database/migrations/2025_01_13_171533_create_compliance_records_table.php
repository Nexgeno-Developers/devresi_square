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
        Schema::create('compliance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null'); // Assuming `properties` table exists
            $table->foreignId('compliance_type_id')->nullable()->constrained('compliance_types')->onDelete('cascade');
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('photos', 2000)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropForeign(['compliance_type_id']);
        });

        Schema::dropIfExists('compliance_records');
    }
};
