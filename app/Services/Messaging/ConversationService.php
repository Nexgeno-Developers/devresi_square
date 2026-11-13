<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\SecureUploadService;
use App\Support\Messaging\Party;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Sending, reading and files inside an existing chat. Chats are never created here. */
class ConversationService
{
    public function __construct(
        private readonly ConversationProvisioner $provisioner,
        private readonly MessagingNotifier $notifier,
        private readonly SecureUploadService $uploads,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function post(Conversation $conversation, Party $as, User $by, ?string $body, array $files = []): Message
    {
        $this->assertParticipant($conversation, $as, $by);
        // The tenancy or job may have ended since the page loaded.
        $conversation = $this->provisioner->syncContext($conversation);
        $this->assertParticipant($conversation, $as, $by);
        if (! $conversation->isActive()) {
            throw new MessagingException($conversation->archived_reason ?: 'This chat is archived.');
        }

        $body = trim((string) $body);
        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
        if ($body === '' && $files === []) {
            throw new MessagingException('Write a message or add a file first.');
        }
        if (mb_strlen($body) > (int) config('messaging.body_max')) {
            throw new MessagingException('Messages can be up to '.config('messaging.body_max').' characters.');
        }
        if ($files !== []) {
            $this->assertFiles($files, $by);
        }

        $message = DB::transaction(function () use ($conversation, $as, $by, $body, $files) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_party_type' => $as->type,
                'sender_party_id' => $as->id,
                'user_id' => $by->id,
                'body' => $body === '' ? null : $body,
                'kind' => Message::TEXT,
            ]);
            foreach ($files as $file) {
                $this->storeAttachment($message, $conversation, $file);
            }
            $conversation->forceFill(['last_message_at' => $message->created_at, 'last_message_id' => $message->id])->save();

            return $message;
        });

        $this->markRead($conversation, $as, $by);
        $this->notifier->messageReceived($conversation, $message);

        return $message;
    }

    public function markRead(Conversation $conversation, Party $as, User $by): void
    {
        $lastId = (int) Message::query()->where('conversation_id', $conversation->id)->max('id');
        $keys = ['conversation_id' => $conversation->id, 'user_id' => $by->id, 'party_type' => $as->type, 'party_id' => $as->id];
        $current = (int) DB::table('conversation_reads')->where($keys)->value('last_read_message_id');
        if ($lastId <= $current) {
            return;
        }
        DB::table('conversation_reads')->updateOrInsert($keys, ['last_read_message_id' => $lastId, 'updated_at' => now()]);
    }

    public function lastReadBy(Conversation $conversation, Party $as, User $by): int
    {
        return (int) DB::table('conversation_reads')
            ->where(['conversation_id' => $conversation->id, 'user_id' => $by->id, 'party_type' => $as->type, 'party_id' => $as->id])
            ->value('last_read_message_id');
    }

    /** Unread messages for this person acting as this party, in chats they are still part of. */
    public function unreadCount(Party $as, User $by): int
    {
        return (int) $this->unreadQuery($as, $by)->count();
    }

    /** @return Collection<int|string, int> unread count keyed by conversation id */
    public function unreadByConversation(Party $as, User $by, Collection $conversationIds): Collection
    {
        if ($conversationIds->isEmpty()) {
            return collect();
        }

        return $this->unreadQuery($as, $by)
            ->whereIn('m.conversation_id', $conversationIds)
            ->groupBy('m.conversation_id')
            ->selectRaw('m.conversation_id, COUNT(*) as unread')
            ->pluck('unread', 'conversation_id');
    }

    /** @return Collection<int, Message> */
    public function messagesAfter(Conversation $conversation, int $afterId, int $limit = 200): Collection
    {
        return $conversation->messages()
            ->with(['attachments', 'author'])
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function assertParticipant(Conversation $conversation, Party $as, User $by): void
    {
        $conversation->loadMissing('participants');
        if (! $conversation->hasParticipant($as) || ! $as->actsFor($by)) {
            abort(404);
        }
    }

    public function attachmentPath(MessageAttachment $attachment): string
    {
        return $this->uploads->resolveRelativePath($attachment->stored_path);
    }

    public function deleteFile(MessageAttachment $attachment): void
    {
        Storage::disk('local')->delete($this->attachmentPath($attachment));
    }

    private function unreadQuery(Party $as, User $by)
    {
        return DB::table('messages as m')
            ->join('conversation_participants as p', function ($join) use ($as) {
                $join->on('p.conversation_id', '=', 'm.conversation_id')
                    ->where('p.party_type', $as->type)->where('p.party_id', $as->id)
                    ->whereNull('p.left_at');
            })
            ->leftJoin('conversation_reads as r', function ($join) use ($as, $by) {
                $join->on('r.conversation_id', '=', 'm.conversation_id')->where('r.user_id', $by->id)->where('r.party_type', $as->type)->where('r.party_id', $as->id);
            })
            // System notes have no sender, so they count as unread for everyone.
            ->where(fn ($q) => $q->whereNull('m.sender_party_type')
                ->orWhere('m.sender_party_type', '!=', $as->type)
                ->orWhere('m.sender_party_id', '!=', $as->id))
            ->whereRaw('m.id > COALESCE(r.last_read_message_id, 0)');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function assertFiles(array $files, User $by): void
    {
        $max = (int) config('messaging.attachments.max_files');
        if (count($files) > $max) {
            throw new MessagingException("You can attach up to {$max} files per message.");
        }
        $key = 'messaging-attachments:'.$by->id;
        $limit = (int) config('messaging.attachments_per_hour');
        if (RateLimiter::tooManyAttempts($key, $limit) || RateLimiter::attempts($key) + count($files) > $limit) {
            throw new MessagingException('You have sent a lot of files. Try again in an hour.');
        }
        $allowed = config('messaging.attachments.extensions', []);
        $maxBytes = (int) config('messaging.attachments.max_kb') * 1024;
        foreach ($files as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($extension, $allowed, true)) {
                throw new MessagingException('“'.Str::limit($file->getClientOriginalName(), 60).'” is not a file type you can send. Use a photo, PDF or office document.');
            }
            if ($file->getSize() > $maxBytes) {
                throw new MessagingException('“'.Str::limit($file->getClientOriginalName(), 60).'” is bigger than '.round($maxBytes / 1048576).' MB.');
            }
            try {
                $this->uploads->assertSafe($file);
            } catch (\Illuminate\Validation\ValidationException) {
                throw new MessagingException('“'.Str::limit($file->getClientOriginalName(), 60).'” could not be checked as a safe file.');
            }
        }
        foreach ($files as $file) {
            RateLimiter::hit($key, 3600);
        }
    }

    /** Every messaging file is private, images included, and served only to participants. */
    private function storeAttachment(Message $message, Conversation $conversation, UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $directory = trim((string) config('messaging.attachments.directory'), '/').'/'.$conversation->id;
        $name = Str::random(40).'.'.$extension;
        $path = $file->storeAs($directory, $name, 'local');
        if (! $path) {
            throw new MessagingException('The file could not be saved. Try again.');
        }
        $original = preg_replace('/[^\pL\pN ._()-]+/u', '_', (string) $file->getClientOriginalName()) ?: 'file.'.$extension;

        MessageAttachment::create([
            'message_id' => $message->id,
            'stored_path' => 'private:'.$path,
            'original_name' => Str::limit($original, 180, ''),
            'mime' => $file->getMimeType(),
            'extension' => $extension,
            'size' => (int) $file->getSize(),
        ]);
    }
}
