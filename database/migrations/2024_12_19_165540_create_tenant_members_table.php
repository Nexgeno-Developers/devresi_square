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
        Schema::create('tenant_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenancy_id')->nullable(); // Explicitly define type
            $table->unsignedBigInteger('user_id')->nullable(); // Explicitly define type
            // $table->string('name')->nullable();
            // $table->string('email')->nullable();
            // $table->string('phone')->nullable();
            // $table->string('employment_status')->nullable();  // Added data type for employmentStatus
            // $table->string('business_name')->nullable();     // Added data type for businessName
            // $table->boolean('guarantee')->nullable();         // Added data type for guarantee
            // $table->boolean('previously_rented')->nullable(); // Added data type for previouslyRented
            // $table->boolean('poor_credit')->nullable();      // Added data type for poorCredit
            $table->boolean('is_main_person')->default(false);
            $table->string('group_id', 255)->nullable(); // Grouping family members
            $table->timestamps();
            $table->foreign('tenancy_id')->references('id')->on('tenancies')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_members');
    }
};
