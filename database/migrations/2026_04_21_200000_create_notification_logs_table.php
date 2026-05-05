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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            // Event key / identifier (invoice_created, rent_due_reminder, etc.)
            $table->string('identifier');

            // Polymorphic notifiable (tenant, user, owner, etc.)
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');

            // email, sms, whatsapp, system
            $table->string('channel');

            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('payload')->nullable();

            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedInteger('attempt')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['identifier', 'channel']);
            $table->index(['status', 'attempt']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

