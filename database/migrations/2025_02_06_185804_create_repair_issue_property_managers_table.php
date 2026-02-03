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
        Schema::create('repair_issue_property_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repair_issue_id');
            $table->unsignedBigInteger('property_manager_id');
            $table->timestamp('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by');   // who assigned property manager.
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('repair_issue_id')->references('id')->on('repair_issues')->onDelete('cascade');
            $table->foreign('property_manager_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_issue_property_managers');
    }
};
