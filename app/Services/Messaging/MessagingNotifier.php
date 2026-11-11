<?php

namespace App\Services\Messaging;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Contractors\ContractorMail;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The only place messaging talks to people outside the chat.
 * In-app (bell) for everything; email only for new message requests.
 * Message text never goes into an email.
 */
class MessagingNotifier
{
    public function __construct(private readonly ContractorMail $mail) {}

    public function requestReceived(MessageRequest $request): void
    {
        $to = $request->toParty();
        if (! $to) {
            return;
        }
        $from = $request->fromParty();
        $area = MessagingArea::forParty($to);
        $url = MessagingArea::route($area, 'requests');
        $title = 'Message request from '.$from->displayName();
        $event = CrmNotificationEvent::MessageRequestReceived->value;

        foreach ($this->users($to) as $user) {
            $accountId = $this->bellAccountFor($user, $to, $from);
            if ($this->wants($user, $event, 'in_app', $accountId)) {
                $this->bell($user, $event, $title, $from->displayName().' ('.$from->roleLabel().') would like to message you.', $url, $accountId);
            }
            if ($this->wants($user, $event, 'email', $accountId)) {
                $this->mail->toUser($user, $title, '<p>'.e($from->displayName()).' ('.e($from->roleLabel()).') would like to message you on ResiSquare.</p>'
                    .'<p><a href="'.e($url).'">Open your message requests</a></p>'
                    .'<p style="color:#666">You can accept, decline or block. You can turn these emails off in your notification settings.</p>');
            }
        }
    }

    public function requestAccepted(MessageRequest $request, Conversation $conversation): void
    {
        $from = $request->fromParty();
        $to = $request->toParty();
        if (! $to) {
            return;
        }
        $event = CrmNotificationEvent::MessageRequestAccepted->value;
        $url = MessagingArea::route(MessagingArea::forParty($from), 'show', $conversation);

        foreach ($this->users($from) as $user) {
            $accountId = $this->bellAccountFor($user, $from, $to);
            if ($this->wants($user, $event, 'in_app', $accountId)) {
                $this->bell($user, $event, $to->displayName().' accepted your message request', 'You can chat now.', $url, $accountId);
            }
        }
    }

    /** One unread bell per conversation per person; later messages refresh it. */
    public function messageReceived(Conversation $conversation, Message $message, Party $recipient): void
    {
        $participant = $conversation->participantFor($recipient);
        if ($participant?->muted_until?->isFuture()) {
            return;
        }
        $sender = $message->senderParty();
        $event = CrmNotificationEvent::MessageReceived->value;
        $url = MessagingArea::route(MessagingArea::forParty($recipient), 'show', $conversation);
        $title = 'New message from '.$sender->displayName();
        $text = $message->attachments()->exists() && blank($message->body) ? 'Sent you a file.' : 'You have a new message.';

        foreach ($this->users($recipient) as $user) {
            if ((int) $user->id === (int) $message->user_id) {
                continue;
            }
            $accountId = $this->bellAccountFor($user, $recipient, $sender);
            if (! $this->wants($user, $event, 'in_app', $accountId)) {
                continue;
            }
            $existing = DB::table('notifications')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->where('event_key', $event)
                ->where('action_url', $url)
                ->whereNull('read_at')
                ->first();
            if ($existing) {
                DB::table('notifications')->where('id', $existing->id)->update(['updated_at' => now(), 'created_at' => now()]);

                continue;
            }
            $this->bell($user, $event, $title, $text, $url, $accountId);
        }
    }

    public function warned(Party $party, string $note): void
    {
        foreach ($this->users($party) as $user) {
            $this->bell($user, 'message.moderation', 'A message you sent was reported', $note ?: 'Please keep messages respectful and on topic. Repeated reports switch messaging off.', null, $this->bellAccountFor($user, $party, null));
        }
    }

    public function alertAdmins(string $subject, string $text, ?string $url = null, ?string $throttleKey = null): void
    {
        try {
            $this->mail->toAdmins($subject, '<p>'.e($text).'</p>'.($url ? '<p><a href="'.e($url).'">Open</a></p>' : ''), $throttleKey);
        } catch (\Throwable $exception) {
            Log::warning('Messaging admin alert failed', ['error' => $exception->getMessage()]);
        }
        foreach (User::role('Super Admin')->get() as $admin) {
            $this->bell($admin, 'message.moderation', $subject, $text, $url, null);
        }
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function users(Party $party)
    {
        return User::query()->whereIn('id', $party->userIds())->get();
    }

    /**
     * The bell only shows notices for the account the person is looking at
     * (contractors see every notice), so pick the account they will see it in.
     */
    private function bellAccountFor(User $user, Party $recipient, ?Party $other): ?int
    {
        if ($recipient->isAccount()) {
            return $recipient->id;
        }
        if ($user->hasRole('Contractor')) {
            return null;
        }
        $memberships = AccountUser::query()->where('user_id', $user->id)->where('status', 'active')->pluck('account_id')->map(fn ($id) => (int) $id);
        if ($other?->isAccount() && $memberships->contains($other->id)) {
            return $other->id;
        }
        if ($user->last_active_account_id && $memberships->contains((int) $user->last_active_account_id)) {
            return (int) $user->last_active_account_id;
        }

        return $memberships->first();
    }

    private function wants(User $user, string $event, string $channel, ?int $accountId): bool
    {
        if (! config('crm_notifications.enabled', true) || ! config('crm_notifications.groups.message', true)) {
            return false;
        }
        // Event keys contain dots, so read the array rather than use config() dot paths.
        $definition = config('crm_notifications.events', [])[$event] ?? [];
        $default = in_array($channel === 'in_app' ? 'system' : 'email', $definition['channels'] ?? ['system'], true);
        $field = $channel === 'in_app' ? 'in_app_enabled' : 'email_enabled';
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_key', $event)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->latest('updated_at')
            ->first();
        if ($pref && $pref->{$field} !== null) {
            return (bool) $pref->{$field};
        }

        return $default && ($channel !== 'email' || filled($user->email));
    }

    private function bell(User $user, string $event, string $title, string $text, ?string $url, ?int $accountId): void
    {
        try {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'account_id' => $accountId ?: null,
                'type' => 'crm_event',
                'event_key' => $event,
                'category' => 'messages',
                'priority' => 'normal',
                'action_url' => $url,
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'data' => json_encode([
                    'event_key' => $event,
                    'title' => $title,
                    'message' => $text,
                    'url' => $url,
                    'category' => 'messages',
                    'priority' => 'normal',
                ], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Messaging in-app notice failed', ['user_id' => $user->id, 'event' => $event, 'error' => $exception->getMessage()]);
        }
    }
}
