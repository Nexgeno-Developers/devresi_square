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
        Schema::create('repair_issue_contractor_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repair_issue_id');
            $table->unsignedBigInteger('contractor_id'); // Assuming contractor is a user or a separate table.
            $table->unsignedBigInteger('assigned_by');   // The property manager who assigned the contractor.
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('quote_attachment')->nullable(); // File path/filename.
            $table->timestamp('contractor_preferred_availability')->nullable();
            $table->string('status')->default('Proposed');  // You can use enum if preferred.
            $table->timestamps();

            // Foreign keys
            $table->foreign('repair_issue_id')->references('id')->on('repair_issues')->onDelete('cascade');
            // Adjust these foreign keys depending on your contractor table structure:
            $table->foreign('contractor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_issue_contractor_assignments');
    }
};
