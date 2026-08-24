<?php

namespace App\Services\Notifications;

use App\Enums\CrmNotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CrmNotificationService
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipients,
        private readonly NotificationPreferenceService $preferences,
    ) {
    }

    public function dispatch(
        CrmNotificationEvent|string $event,
        Model $subject,
        array $context = [],
        ?User $actor = null,
    ): int {
        if (! config('crm_notifications.enabled', true)) {
            return 0;
        }

        $eventKey = $event instanceof CrmNotificationEvent ? $event->value : $event;
        $group = Str::before($eventKey, '.');
        if (! (bool) config("crm_notifications.groups.{$group}", true)) {
            return 0;
        }
        $definition = config('crm_notifications.events', [])[$eventKey] ?? null;
        if (! is_array($definition)) {
            throw new \InvalidArgumentException("Unknown CRM notification event [{$eventKey}].");
        }
        if (! empty($context['channels'])) {
            $definition['channels'] = collect((array) $context['channels'])->map(fn ($channel) => match ($channel) {
                'in_app', 'database', 'push' => 'system',
                default => $channel,
            })->filter(fn ($channel) => in_array($channel, ['email', 'system'], true))->unique()->values()->all();
        }

        $accountId = (int) ($context['account_id'] ?? $subject->account_id ?? current_account_id());
        if ($accountId <= 0) {
            throw new \InvalidArgumentException("Notification [{$eventKey}] requires an account context.");
        }

        $recipients = $this->recipients->resolve($eventKey, $subject, $accountId, $context);
        if (($context['exclude_actor'] ?? false) && $actor) {
            $recipients = $recipients->reject(fn (User $user) => $user->is($actor));
        }

        $template = EmailTemplate::forAccount($accountId)->where('identifier', $eventKey)->where('status', 1)->first();
        if ($template && (! $this->templateIsValid((string) $template->subject, $definition) || ! $this->templateIsValid((string) $template->default_text, $definition))) {
            Log::error('CRM notification template contains unsupported placeholders.', [
                'template_id' => $template->id,
                'account_id' => $accountId,
                'event_key' => $eventKey,
            ]);
            $template = null;
        }
        $payload = $this->payload($eventKey, $definition, $subject, $context, $accountId, $actor);
        $created = 0;

        foreach ($recipients as $recipient) {
            $recipientPayload = array_merge($payload, [
                'recipient_name' => $recipient->name ?: $recipient->email,
                'recipient_email' => $recipient->email,
            ]);
            $subjectText = $this->render($template?->subject ?: $definition['subject'], $recipientPayload);
            $message = $this->render($template?->default_text ?: $definition['message'], $recipientPayload);
            foreach ($this->preferences->channels($accountId, $recipient, $eventKey, $definition) as $channel) {
                $milestone = (string) ($context['milestone'] ?? $context['version'] ?? 'event');
                $idempotencyKey = hash('sha256', implode('|', [
                    $accountId, $eventKey, $subject->getMorphClass(), $subject->getKey(),
                    $recipient->id, $channel, $milestone,
                ]));

                $log = NotificationLog::firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'account_id' => $accountId,
                        'identifier' => $eventKey,
                        'notifiable_type' => $recipient->getMorphClass(),
                        'notifiable_id' => $recipient->getKey(),
                        'subject_type' => $subject->getMorphClass(),
                        'subject_id' => $subject->getKey(),
                        'actor_id' => $actor?->id,
                        'channel' => $channel,
                        'recipient' => $channel === 'email' ? $recipient->email : null,
                        'subject' => $subjectText,
                        'message' => $message,
                        'payload' => $recipientPayload,
                        'status' => 'pending',
                        'attempt' => 0,
                        'max_attempts' => (int) config('crm_notifications.max_attempts', 3),
                        'scheduled_for' => $context['scheduled_for'] ?? null,
                    ]
                );

                if ($log->wasRecentlyCreated) {
                    SendNotificationJob::dispatch($log)->afterCommit();
                    $created++;
                }
            }
        }

        return $created;
    }

    private function payload(string $eventKey, array $definition, Model $subject, array $context, int $accountId, ?User $actor): array
    {
        $data = Arr::except($context, ['recipients']);
        $data['event_key'] = $eventKey;
        $data['account_id'] = $accountId;
        $data['category'] = $definition['category'];
        $data['priority'] = $definition['priority'];
        $data['subject_type'] = $subject->getMorphClass();
        $data['subject_id'] = $subject->getKey();
        $data['actor_id'] = $actor?->id;
        $data['action_url'] = $context['action_url'] ?? null;
        $data['occurred_at'] = now()->toIso8601String();
        $account = Account::find($accountId);
        $data['brand_name'] = $context['brand_name'] ?? $account?->account_name ?? config('app.name');
        $data['reply_to'] = $context['reply_to'] ?? $account?->billing_email ?? config('mail.from.address');
        $data['reply_name'] = $context['reply_name'] ?? $account?->account_name ?? config('mail.from.name');
        $data['currency'] = $account?->currency ?: 'GBP';

        return $data;
    }

    private function render(string $template, array $data): string
    {
        $values = collect($data)->filter(fn ($value) => is_scalar($value) || $value === null)
            ->map(fn ($value) => $value === null ? '' : (string) $value)->all();

        return Str::limit(trim(render_template($template, $values)), 65000, '');
    }

    private function templateIsValid(string $template, array $definition): bool
    {
        preg_match_all('/\[\[([a-zA-Z0-9_]+)\]\]/', $template, $matches);
        $allowed = array_merge($definition['placeholders'] ?? [], ['action_url']);

        return empty(array_diff(array_unique($matches[1] ?? []), $allowed));
    }
}
