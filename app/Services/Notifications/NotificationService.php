<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    /**
     * Trigger notifications for a given identifier and notifiable.
     *
     * Static entrypoint for convenience:
     *   NotificationService::trigger('rent_due_reminder', $tenant, [...]);
     */
    public static function trigger(string $identifier, Model $notifiable, array $data = []): void
    {
        app(self::class)->triggerNotification($identifier, $notifiable, $data);
    }

    public function triggerNotification(string $identifier, Model $notifiable, array $data = []): void
    {
        $templates = [];

        $emailTemplates = EmailTemplate::query()
            ->where('identifier', $identifier)
            ->where('status', 1)
            ->get();

        foreach ($emailTemplates as $template) {
            $templates[] = [
                'channel' => 'email',
                'subject' => $template->subject,
                'message' => $template->default_text,
            ];
        }

        $smsTemplates = collect();
        if (Schema::hasTable('sms_templates')) {
            $smsTemplates = DB::table('sms_templates')
                ->where('identifier', $identifier)
                ->where('status', 1)
                ->get();
        }

        foreach ($smsTemplates as $template) {
            $templates[] = [
                'channel' => 'sms',
                'subject' => null,
                'message' => $template->sms_body,
            ];
        }

        // Optional / mock channels (disabled by default)
        if (config('notification_system.enable_whatsapp', false) && $smsTemplates->isNotEmpty()) {
            foreach ($smsTemplates as $template) {
                $templates[] = [
                    'channel' => 'whatsapp',
                    'subject' => null,
                    'message' => $template->sms_body,
                ];
            }
        }

        if (config('notification_system.enable_system', false)) {
            $systemMessage = null;
            if ($smsTemplates->isNotEmpty()) {
                $systemMessage = (string) ($smsTemplates->first()->sms_body ?? '');
            } elseif ($emailTemplates->isNotEmpty()) {
                $systemMessage = strip_tags((string) ($emailTemplates->first()->default_text ?? ''));
            }

            if (! empty($systemMessage)) {
                $templates[] = [
                    'channel' => 'system',
                    'subject' => null,
                    'message' => $systemMessage,
                ];
            }
        }

        foreach ($templates as $template) {
            $channel = $template['channel'];

            $recipient = match ($channel) {
                'email' => data_get($notifiable, 'email'),
                'sms', 'whatsapp' => data_get($notifiable, 'phone'),
                'system' => null,
                default => null,
            };

            $subject = $template['subject'];
            $message = $template['message'];

            if ($channel === 'email') {
                $subject = $subject !== null ? render_template((string) $subject, $data) : null;
                $message = render_template((string) $message, $data);
            } else {
                $subject = null;
                $message = replaceVariables((string) $message, $data);
            }

            $log = NotificationLog::create([
                'identifier' => $identifier,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => $notifiable->getKey(),
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => $subject,
                'message' => $message,
                'payload' => $data,
                'status' => 'pending',
                'attempt' => 0,
                'max_attempts' => (int) config('notification_system.max_attempts', 3),
            ]);

            SendNotificationJob::dispatch($log)->afterCommit();
        }
    }
}

