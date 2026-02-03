<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_instance_id')->constrained()->onDelete('cascade');
            $table->integer('minutes_before');           // e.g. 30
            $table->enum('channel', ['email', 'in_app', 'sms', 'push'])->default('email');
            $table->boolean('sent')->default(false);
            $table->timestamps();
        });*/
        
        Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();

            // Now linked to the unified `events` table
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade');

            $table->integer('minutes_before')->default(0);
            $table->enum('channel', ['email', 'in_app', 'sms', 'push']);
            $table->boolean('sent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reminders');
    }
};
