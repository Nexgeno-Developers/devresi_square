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
        Schema::create('property_manager_tenancy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenancy_id');
            $table->unsignedBigInteger('property_manager_id');
            $table->unsignedBigInteger('property_id'); // Adding the property_id column
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('tenancy_id')->references('id')->on('tenancies')->onDelete('cascade');
            $table->foreign('property_manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');  // Reference property_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_manager_tenancy');
    }
};
