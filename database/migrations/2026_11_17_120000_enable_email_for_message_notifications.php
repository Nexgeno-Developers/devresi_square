<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chat messages now send an email as well as a bell. Until now the settings
 * page offered no email option for messages, so saving it stored
 * email_enabled = false for every message preference. Clear those so people
 * get the new default (email on) and can switch it off themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            return;
        }

        DB::table('notification_preferences')
            ->where('event_key', 'message.received')
            ->where('email_enabled', false)
            ->update(['email_enabled' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to restore: the cleared values were never chosen by anyone.
    }
};
