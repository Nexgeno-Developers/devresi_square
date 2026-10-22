<?php

namespace App\Services\Notifications;

use App\Mail\MailManager;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SecurityNotice
{
    public function emailTo(string $address, string $subject, string $html): bool
    {
        if (blank($address)) {
            return false;
        }

        try {
            Mail::to($address)->send(new MailManager([
                'subject' => $subject,
                'content' => $html,
                'attachments' => [],
            ]));

            return true;
        } catch (\Throwable $exception) {
            Log::error('Security email failed: '.$exception->getMessage(), [
                'email' => $address,
                'subject' => $subject,
            ]);

            return false;
        }
    }

    public function inApp(?int $accountId, User $user, string $identifier, string $subject, string $message, ?Model $about = null, ?string $url = null): void
    {
        if (! $accountId) {
            return;
        }

        $uuid = (string) Str::uuid();
        NotificationLog::query()->create([
            'account_id' => $accountId,
            'identifier' => $identifier,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'subject_type' => $about?->getMorphClass(),
            'subject_id' => $about?->getKey(),
            'notification_uuid' => $uuid,
            'idempotency_key' => hash('sha256', $identifier.'|'.$user->id.'|'.Str::uuid()),
            'channel' => 'system',
            'subject' => $subject,
            'message' => $message,
            'payload' => ['action_url' => $url, 'category' => 'system', 'priority' => 'high'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => $uuid,
            'account_id' => $accountId,
            'type' => 'crm_event',
            'event_key' => $identifier,
            'category' => 'system',
            'priority' => 'high',
            'action_url' => $url,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'data' => json_encode([
                'event_key' => $identifier,
                'title' => $subject,
                'message' => $message,
                'url' => $url,
                'category' => 'system',
                'priority' => 'high',
            ], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function endOtherSessions(User $user): void
    {
        $table = config('session.table', 'sessions');
        if (! Schema::hasTable($table)) {
            return;
        }

        $current = (string) session()->getId();
        DB::table($table)
            ->where('user_id', $user->id)
            ->when($current !== '', fn ($query) => $query->where('id', '!=', $current))
            ->delete();
    }
}
