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
        /*Schema::create('events', function (Blueprint $table) {
            $table->id();
                        // Basic fields
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->string('office')->nullable();
            $table->enum('status', ['Confirmed', 'Pending', 'Cancelled', 'Rescheduled'])
                  ->default('Pending');

            $table->string('diary_owner')->nullable();
            $table->string('on_behalf_of')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('reminder')->nullable(); 
            // (e.g. “30 minutes”, or store a number/unit pair if you prefer)

            // Recurrence rule
            $table->enum('repeat', ['none', 'daily', 'weekly', 'monthly'])
                  ->default('none');
            $table->integer('repeat_interval')->nullable()
                  ->default(1)
                  ->comment('Every N days/weeks/months; default 1');
            $table->integer('repeat_until_count')->nullable()
                  ->comment('Number of occurrences (excluding the original).');

            // Or, alternatively, you could store a repeat_end_date instead of a count.
            // Here we’ll use a count approach for simplicity.
            $table->timestamps();
        });*/

        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Recurrence parent (null = master)
            $table->foreignId('parent_id')->nullable()->constrained('events')->onDelete('set null');

            // Event metadata
            $table->string('title');
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('sub_type_id')->nullable();
            $table->string('office')->nullable();
            $table->enum('status', ['Confirmed', 'Pending', 'Cancelled', 'Rescheduled', 'Scheduled'])->default('Pending');
            $table->string('diary_owner')->nullable();
            $table->string('on_behalf_of')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('reminder')->nullable(); // e.g., "30 minutes"

            // Date/time
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            // Recurrence fields (only used in master)
            $table->string('rrule')->nullable();     // Full RRULE string
            $table->text('exdates')->nullable();     // JSON of exclusion dates: ["2025-06-17", "2025-06-20"]

            // Status of this specific instance
            $table->boolean('is_exception')->default(false); // If this instance overrides default pattern
            $table->enum('instance_status', ['Scheduled', 'Cancelled', 'Completed', 'Rescheduled'])->nullable();

            $table->timestamps();

            // Indexes
            $table->index('start_datetime');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
