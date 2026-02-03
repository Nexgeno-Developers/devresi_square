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
        Schema::create('event_instances', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to master event
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade');

            // Each instance’s scheduled datetime
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            // Per-instance status (e.g. if the user cancels or reschedules only this)
            $table->enum('instance_status', ['Scheduled','Cancelled','Rescheduled'])
                  ->default('Scheduled');

            // Track if this instance has already had a reminder sent
            $table->boolean('notified')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_instances');
    }
};
