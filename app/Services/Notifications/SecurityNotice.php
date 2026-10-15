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

    public function inApp(?int $accountId, User $user, string $identifier, string $subject, string $message, ?Model $about = null): void
    {
        if (! $accountId) {
            return;
        }

        NotificationLog::query()->create([
            'account_id' => $accountId,
            'identifier' => $identifier,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'subject_type' => $about?->getMorphClass(),
            'subject_id' => $about?->getKey(),
            'notification_uuid' => (string) Str::uuid(),
            'idempotency_key' => hash('sha256', $identifier.'|'.$user->id.'|'.Str::uuid()),
            'channel' => 'system',
            'subject' => $subject,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
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
