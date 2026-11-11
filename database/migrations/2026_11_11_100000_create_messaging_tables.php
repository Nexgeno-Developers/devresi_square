<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct messaging between parties. A party is either a SaaS account (shared
 * inbox for its staff) or a single user (tenant, contractor, portal contact).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('pair_key', 120)->unique();
            $table->string('from_type', 10);
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 10);
            $table->unsignedBigInteger('to_id')->nullable();
            // What the sender typed (email requests show this until accepted).
            $table->string('to_label', 191)->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('intro')->nullable();
            $table->boolean('intro_masked')->default(false);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedTinyInteger('decline_count')->default(0);
            $table->timestamp('cooldown_until')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->timestamps();

            $table->index(['to_type', 'to_id', 'status']);
            $table->index(['from_type', 'from_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('pair_key', 120)->unique();
            $table->unsignedBigInteger('request_id')->nullable();
            $table->string('origin', 20)->default('request');
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('read_only_reason', 191)->nullable();
            // Contact details are hidden until both sides have written.
            $table->timestamp('unmasked_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('party_type', 10);
            $table->unsignedBigInteger('party_id');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'party_type', 'party_id'], 'conv_participant_unique');
            $table->index(['party_type', 'party_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_party_type', 10);
            $table->unsignedBigInteger('sender_party_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->boolean('masked')->default(false);
            $table->string('kind', 10)->default('text');
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('stored_path', 255);
            $table->string('original_name', 191);
            $table->string('mime', 120)->nullable();
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('conversation_reads', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('party_type', 10);
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->primary(['conversation_id', 'user_id', 'party_type', 'party_id'], 'conv_reads_primary');
        });

        Schema::create('message_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('blocker_type', 10);
            $table->unsignedBigInteger('blocker_id');
            $table->string('blocked_type', 10);
            $table->unsignedBigInteger('blocked_id');
            $table->foreignId('blocked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['blocker_type', 'blocker_id', 'blocked_type', 'blocked_id'], 'message_blocks_unique');
            $table->index(['blocked_type', 'blocked_id', 'created_at']);
        });

        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_party_type', 10);
            $table->unsignedBigInteger('reporter_party_id');
            $table->string('reported_party_type', 10)->nullable();
            $table->unsignedBigInteger('reported_party_id')->nullable();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('message_requests')->nullOnDelete();
            $table->string('category', 20);
            $table->string('note', 1000)->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('outcome', 20)->nullable();
            $table->string('outcome_note', 1000)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Pauses and suspensions are per party (an account inbox or a person).
        Schema::create('messaging_party_flags', function (Blueprint $table) {
            $table->id();
            $table->string('party_type', 10);
            $table->unsignedBigInteger('party_id');
            $table->timestamp('paused_until')->nullable();
            $table->timestamp('last_paused_at')->nullable();
            $table->unsignedSmallInteger('pause_count')->default(0);
            $table->timestamp('suspended_until')->nullable();
            $table->string('suspended_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['party_type', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_party_flags');
        Schema::dropIfExists('message_reports');
        Schema::dropIfExists('message_blocks');
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('message_requests');
    }
};
