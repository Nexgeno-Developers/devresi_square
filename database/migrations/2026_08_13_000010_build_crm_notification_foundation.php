<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'timezone')) {
                $table->string('timezone', 64)->default('Europe/London')->after('currency');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'account_id')) $table->unsignedBigInteger('account_id')->nullable()->after('id');
            if (! Schema::hasColumn('notifications', 'event_key')) $table->string('event_key')->nullable()->after('type');
            if (! Schema::hasColumn('notifications', 'category')) $table->string('category', 50)->nullable()->after('event_key');
            if (! Schema::hasColumn('notifications', 'priority')) $table->string('priority', 20)->default('normal')->after('category');
            if (! Schema::hasColumn('notifications', 'action_url')) $table->text('action_url')->nullable()->after('priority');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'account_id', 'read_at'], 'notifications_account_unread_idx');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('id');
            $table->string('subject_type')->nullable()->after('notifiable_id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->unsignedBigInteger('actor_id')->nullable()->after('subject_id');
            $table->uuid('notification_uuid')->nullable()->after('actor_id');
            $table->string('idempotency_key', 64)->nullable()->after('notification_uuid');
            $table->timestamp('scheduled_for')->nullable()->after('max_attempts');
            $table->unique('idempotency_key', 'notification_logs_idempotency_unique');
            $table->index(['account_id', 'status', 'created_at'], 'notification_logs_account_status_idx');
            $table->index(['subject_type', 'subject_id'], 'notification_logs_subject_idx');
        });

        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_key');
            $table->boolean('email_enabled')->nullable();
            $table->boolean('in_app_enabled')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'event_key']);
            $table->unique(['account_id', 'user_id', 'event_key'], 'notification_preferences_unique');
            });
        }

        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->string('receiver')->nullable();
                $table->string('identifier');
                $table->string('email_type')->nullable();
                $table->text('subject')->nullable();
                $table->longText('default_text')->nullable();
                $table->boolean('status')->default(true);
                $table->boolean('is_status_changeable')->default(true);
                $table->boolean('is_dafault_text_editable')->default(true);
                $table->string('addon')->nullable();
                $table->timestamps();
                $table->index(['account_id', 'identifier'], 'email_templates_account_identifier_idx');
            });
        } else {
            Schema::table('email_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('email_templates', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->after('id');
                    $table->index(['account_id', 'identifier'], 'email_templates_account_identifier_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropUnique('notification_logs_idempotency_unique');
            $table->dropIndex('notification_logs_account_status_idx');
            $table->dropIndex('notification_logs_subject_idx');
            $table->dropColumn(['account_id', 'subject_type', 'subject_id', 'actor_id', 'notification_uuid', 'idempotency_key', 'scheduled_for']);
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_account_unread_idx');
            $table->dropColumn(['account_id', 'event_key', 'category', 'priority', 'action_url']);
        });
        Schema::table('email_templates', function (Blueprint $table) {
            if (Schema::hasColumn('email_templates', 'account_id')) {
                $table->dropIndex('email_templates_account_identifier_idx');
                $table->dropColumn('account_id');
            }
        });
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'timezone')) $table->dropColumn('timezone');
        });
    }
};
