<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('event_instance_changes', function (Blueprint $table) {
            $table->id();

            // Link back to the instance that changed
            // $table->foreignId('event_instance_id')
            //       ->constrained('event_instances')
            //       ->onDelete('cascade');

            // Link back to the unified events table (instance or master)
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade');

            // Which field changed (e.g. 'start_datetime', 'end_datetime', 'instance_status')
            $table->string('changed_field');

            // Old and new values (we’ll store as TEXT in case JSON or large strings are needed)
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            // Who made the change (if you have users/auth)
            $table->foreignId('changed_by')->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamp('changed_at')->useCurrent();

            // Optionally, store a short comment if needed
            $table->string('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_instance_changes');
    }
};
