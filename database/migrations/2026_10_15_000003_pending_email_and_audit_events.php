<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'pending_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pending_email')->nullable()->after('email');
            });
        }

        if (! Schema::hasTable('email_change_tokens')) {
            Schema::create('email_change_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('email');
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_events')) {
            Schema::create('audit_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('action');
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->timestamps();
                $table->index(['action', 'subject_type', 'subject_id'], 'audit_events_subject_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('email_change_tokens');
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'pending_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('pending_email');
            });
        }
    }
};
