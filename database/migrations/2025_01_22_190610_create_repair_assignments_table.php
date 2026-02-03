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
        Schema::create('repair_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_issue_id')->constrained('repair_issues')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in-progress', 'completed', 'closed']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_assignments');
    }
};
