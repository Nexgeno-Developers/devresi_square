<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chats exist only because of a tenancy (landlord + its tenants) or an
 * assigned repair / compliance job (landlord + contractor + tenants).
 * Nobody can start any other chat. A party is a SaaS account (shared inbox
 * for its staff) or a single user (tenant, contractor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            // tenancy | repair_job | compliance_job
            $table->string('kind', 20);
            // tenancies.id, repair_job_offers.id or compliance_job_offers.id
            $table->unsignedBigInteger('context_id');
            // The landlord / agency account the chat belongs to.
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->string('archived_reason', 191)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'context_id']);
            $table->index(['account_id', 'status']);
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('party_type', 10);
            $table->unsignedBigInteger('party_id');
            // landlord | tenant | contractor
            $table->string('role', 20);
            // Set when a tenant leaves the tenancy while the chat is live.
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'party_type', 'party_id'], 'conv_participant_unique');
            $table->index(['party_type', 'party_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            // Null for system notes ("Job assigned", "Chat archived").
            $table->string('sender_party_type', 10)->nullable();
            $table->unsignedBigInteger('sender_party_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('kind', 10)->default('text');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_reads');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
