<?php

namespace App\Services\Messaging;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Contractors\ContractorMail;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The only place messaging talks to people outside the chat: an in-app bell
 * for new messages (no email per message, no message text in notices) and
 * an email to ResiSquare support when someone reports a chat.
 */
class MessagingNotifier
{
    public function __construct(private readonly ContractorMail $mail) {}

    /** One unread bell per chat per person; later messages refresh it. */
    public function messageReceived(Conversation $conversation, Message $message): void
    {
        $sender = $message->senderParty();
        $title = 'New message from '.($sender?->displayName() ?? 'ResiSquare');
        $text = $message->attachments()->exists() && blank($message->body) ? 'Sent a file.' : 'You have a new message.';

        foreach ($conversation->currentParticipants() as $participant) {
            if ($sender && $participant->party()->is($sender)) {
                continue;
            }
            $this->notifyParticipant($conversation, $participant, $title, $text, (int) $message->user_id);
        }
    }

    public function chatOpened(Conversation $conversation, Message $message): void
    {
        foreach ($conversation->currentParticipants() as $participant) {
            $this->notifyParticipant($conversation, $participant, 'New job chat', Str::limit((string) $message->body, 140), 0);
        }
    }

    public function chatReported(Conversation $conversation, User $reporter, Party $as, string $note): void
    {
        $text = 'Chat '.$conversation->public_id.' ('.$conversation->kind.', account '.$conversation->account_id.') was reported by user '.$reporter->id
            .' acting as '.$as->displayName().' ('.$as->roleLabel().').';
        try {
            $this->mail->toAdmins('A chat was reported', '<p>'.e($text).'</p><p><strong>Their note:</strong><br>'.nl2br(e($note)).'</p>', 'chat-report-'.$conversation->id.'-'.$reporter->id);
        } catch (\Throwable $exception) {
            Log::warning('Chat report email failed', ['conversation' => $conversation->id, 'error' => $exception->getMessage()]);
        }
        Log::channel('single')->info('messaging.chat_reported', ['conversation' => $conversation->public_id, 'reporter' => $reporter->id]);
    }

    private function notifyParticipant(Conversation $conversation, ConversationParticipant $participant, string $title, string $text, int $skipUserId): void
    {
        $party = $participant->party();
        $event = CrmNotificationEvent::MessageReceived->value;
        $url = MessagingArea::route(MessagingArea::forParticipant($participant), 'show', $conversation);

        foreach (User::query()->whereIn('id', $party->userIds())->get() as $user) {
            if ((int) $user->id === $skipUserId) {
                continue;
            }
            $accountId = $this->bellAccountFor($user, $party, (int) $conversation->account_id);
            if (! $this->wants($user, $event, $accountId)) {
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

    /**
     * The bell only shows notices for the account the person is looking at
     * (contractors see every notice), so pick the account they will see it in.
     */
    private function bellAccountFor(User $user, Party $recipient, int $chatAccountId): ?int
    {
        if ($recipient->isAccount()) {
            return $recipient->id;
        }
        if ($user->hasRole('Contractor')) {
            return null;
        }
        $memberships = AccountUser::query()->where('user_id', $user->id)->where('status', 'active')->pluck('account_id')->map(fn ($id) => (int) $id);
        if ($memberships->contains($chatAccountId)) {
            return $chatAccountId;
        }

        return $memberships->first();
    }

    private function wants(User $user, string $event, ?int $accountId): bool
    {
        if (! config('crm_notifications.enabled', true) || ! config('crm_notifications.groups.message', true)) {
            return false;
        }
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_key', $event)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->latest('updated_at')
            ->first();
        if ($pref && $pref->in_app_enabled !== null) {
            return (bool) $pref->in_app_enabled;
        }

        return true;
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
