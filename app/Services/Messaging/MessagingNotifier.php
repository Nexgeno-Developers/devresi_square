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
 * and an email for every new message (each with a short preview), and an
 * email to ResiSquare support when someone reports a chat.
 */
class MessagingNotifier
{
    /** Longest preview of a message shown in a bell. */
    private const PREVIEW_LENGTH = 120;

    public function __construct(private readonly ContractorMail $mail) {}

    /** One bell per message for everyone else in the chat. Opening the chat clears them. */
    public function messageReceived(Conversation $conversation, Message $message): void
    {
        $sender = $message->senderParty();
        $from = $sender?->displayName() ?? 'ResiSquare';
        if ($sender?->isAccount() && $message->author) {
            $from = $message->author->name.' ('.$from.')';
        }
        $title = 'New message from '.$from;
        $text = $this->preview($message);

        foreach ($conversation->currentParticipants() as $participant) {
            if ($sender && $participant->party()->is($sender)) {
                continue;
            }
            $this->notifyParticipant($conversation, $participant, $title, $text, (int) $message->user_id, '#m-'.$message->public_id, $from);
        }
    }

    public function chatOpened(Conversation $conversation, Message $message): void
    {
        foreach ($conversation->currentParticipants() as $participant) {
            $this->notifyParticipant($conversation, $participant, 'New job chat', Str::limit((string) $message->body, 140), 0);
        }
    }

    /** Mark this person's unread message bells for a chat as read (they are looking at it). */
    public function clearFor(Conversation $conversation, User $user): void
    {
        try {
            DB::table('notifications')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->where('event_key', CrmNotificationEvent::MessageReceived->value)
                ->whereNull('read_at')
                ->where('action_url', 'like', '%/messages/c/'.$conversation->public_id.'%')
                ->update(['read_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Clearing message bells failed', ['conversation' => $conversation->id, 'user_id' => $user->id, 'error' => $exception->getMessage()]);
        }
    }

    private function preview(Message $message): string
    {
        $body = trim((string) preg_replace('/\s+/u', ' ', (string) $message->body));
        $files = $message->attachments()->count();
        if ($body === '') {
            return $files > 1 ? 'Sent '.$files.' files.' : 'Sent a file.';
        }

        return Str::limit($body, self::PREVIEW_LENGTH).($files ? ' (+'.$files.' '.Str::plural('file', $files).')' : '');
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

    private function notifyParticipant(Conversation $conversation, ConversationParticipant $participant, string $title, string $text, int $skipUserId, ?string $anchor = null, ?string $from = null): void
    {
        $party = $participant->party();
        $event = CrmNotificationEvent::MessageReceived->value;
        $url = MessagingArea::route(MessagingArea::forParticipant($participant), 'show', $conversation).($anchor ?? '');
        $perMessage = $anchor !== null;

        foreach (User::query()->whereIn('id', $party->userIds())->get() as $user) {
            if ((int) $user->id === $skipUserId) {
                continue;
            }
            $accountId = $this->bellAccountFor($user, $party, (int) $conversation->account_id);
            if ($perMessage && $this->wants($user, $event, $accountId, 'email')) {
                $this->email($user, $title, $text, $url, (string) $from);
            }
            if (! $this->wants($user, $event, $accountId)) {
                continue;
            }
            // A message bell is always new; only the one-off "chat opened" notice is de-duplicated.
            $existing = $perMessage ? null : DB::table('notifications')
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

    /** $channel is 'system' (the bell) or 'email'. Both are on unless the person turned them off. */
    private function wants(User $user, string $event, ?int $accountId, string $channel = 'system'): bool
    {
        $field = $channel === 'email' ? 'email_enabled' : 'in_app_enabled';
        if (! config('crm_notifications.enabled', true) || ! config('crm_notifications.groups.message', true)) {
            return false;
        }
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('event_key', $event)
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->latest('updated_at')
            ->first();
        if ($pref && $pref->{$field} !== null) {
            return (bool) $pref->{$field};
        }

        return true;
    }

    private function email(User $user, string $title, string $text, string $url, string $from): void
    {
        if (! $user->email) {
            return;
        }
        $html = '<p>Hi '.e($user->name).',</p>'
            .'<p><strong>'.e($from).'</strong> sent you a message on '.e(config('app.name')).':</p>'
            .'<blockquote style="margin:16px 0;padding:12px 16px;border-left:4px solid #2a7ae2;background:#f4f6f9;color:#333;">'.nl2br(e($text)).'</blockquote>'
            .'<p><a href="'.e($url).'" style="display:inline-block;padding:10px 18px;background:#2a7ae2;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Open the chat to reply</a></p>'
            .'<p style="font-size:13px;color:#777;">Please reply in the chat, not to this email. You can turn these emails off in your notification settings.</p>';
        $this->mail->toUser($user, $title, $html);
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
