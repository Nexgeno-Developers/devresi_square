<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\SecureUploadService;
use App\Support\ContactMasker;
use App\Support\Messaging\Party;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConversationService
{
    public function __construct(
        private readonly MessagingGuard $guard,
        private readonly MessagingNotifier $notifier,
        private readonly RelationshipChecker $relationships,
        private readonly SecureUploadService $uploads,
    ) {}

    public function forPair(Party $a, Party $b): ?Conversation
    {
        return Conversation::query()->where('pair_key', Party::pairKey($a, $b))->first();
    }

    public function create(Party $a, Party $b, string $origin, ?int $originId, ?int $requestId, bool $unmasked): Conversation
    {
        return DB::transaction(function () use ($a, $b, $origin, $originId, $requestId, $unmasked) {
            $conversation = Conversation::create([
                'pair_key' => Party::pairKey($a, $b),
                'request_id' => $requestId,
                'origin' => $origin,
                'origin_id' => $originId,
                'status' => Conversation::ACTIVE,
                'unmasked_at' => $unmasked ? now() : null,
            ]);
            foreach ([$a, $b] as $party) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'party_type' => $party->type,
                    'party_id' => $party->id,
                ]);
            }

            return $conversation->load('participants');
        });
    }

    /** Re-checks the relationship; ended ones become read-only (and reopen if it is live again). */
    public function refreshStatus(Conversation $conversation): Conversation
    {
        $conversation->loadMissing('participants');
        $reason = $this->relationships->endedReason($conversation);
        if ($reason && $conversation->isActive()) {
            $conversation->forceFill(['status' => Conversation::READ_ONLY, 'read_only_reason' => $reason])->save();
        } elseif (! $reason && ! $conversation->isActive() && $conversation->read_only_reason !== null) {
            $conversation->forceFill(['status' => Conversation::ACTIVE, 'read_only_reason' => null])->save();
        }

        return $conversation;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function post(Conversation $conversation, Party $as, User $by, ?string $body, array $files = []): Message
    {
        $conversation->loadMissing('participants');
        $this->assertParticipant($conversation, $as, $by);
        $this->refreshStatus($conversation);
        if (! $conversation->isActive()) {
            throw new MessagingException($conversation->read_only_reason ?: 'This conversation is read-only.');
        }
        $other = $conversation->otherParty($as);
        if (! $other || $this->guard->isBlocked($as, $other)) {
            throw new MessagingException('You can’t reply to this conversation.');
        }
        $this->guard->assertCanSend($as);

        $body = trim((string) $body);
        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
        if ($body === '' && $files === []) {
            throw new MessagingException('Write a message or add a file first.');
        }
        if (mb_strlen($body) > (int) config('messaging.body_max')) {
            throw new MessagingException('Messages can be up to '.config('messaging.body_max').' characters.');
        }

        // The first reply from the other side lifts contact masking for both.
        $otherHasWritten = $conversation->messages()
            ->where('sender_party_type', $other->type)->where('sender_party_id', $other->id)
            ->where('kind', Message::TEXT)->exists();
        $unmaskNow = $conversation->unmasked_at === null && $otherHasWritten;
        $unmasked = $conversation->unmasked_at !== null || $unmaskNow;

        if ($files !== []) {
            if (! $unmasked) {
                throw new MessagingException('You can send files once you have both replied.');
            }
            $this->assertFiles($files, $by);
        }

        $masked = false;
        if (! $unmasked && $body !== '') {
            [$body, $masked] = ContactMasker::mask($body);
        }

        $message = DB::transaction(function () use ($conversation, $as, $by, $body, $masked, $files, $unmaskNow) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_party_type' => $as->type,
                'sender_party_id' => $as->id,
                'user_id' => $by->id,
                'body' => $body === '' ? null : $body,
                'masked' => $masked,
                'kind' => Message::TEXT,
            ]);
            foreach ($files as $file) {
                $this->storeAttachment($message, $conversation, $file);
            }
            $conversation->forceFill([
                'last_message_at' => $message->created_at,
                'last_message_id' => $message->id,
                'unmasked_at' => $unmaskNow ? now() : $conversation->unmasked_at,
            ])->save();
            // A new message brings an archived conversation back for the other side.
            ConversationParticipant::query()->where('conversation_id', $conversation->id)->update(['archived_at' => null]);

            return $message;
        });

        $this->markRead($conversation, $as, $by);
        $this->notifier->messageReceived($conversation, $message, $other);

        return $message;
    }

    /** Adds a message written by the system or carried over from a request intro. */
    public function addIntro(Conversation $conversation, Party $from, ?int $userId, string $body, bool $masked): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_party_type' => $from->type,
            'sender_party_id' => $from->id,
            'user_id' => $userId,
            'body' => $body,
            'masked' => $masked,
            'kind' => Message::TEXT,
        ]);
        $conversation->forceFill(['last_message_at' => $message->created_at, 'last_message_id' => $message->id])->save();

        return $message;
    }

    public function unsend(Message $message, Party $as, User $by): void
    {
        if ((int) $message->user_id !== (int) $by->id || ! $message->sentBy($as)) {
            throw new MessagingException('You can only take back your own messages.');
        }
        if ($message->created_at->lt(now()->subMinutes((int) config('messaging.unsend_minutes')))) {
            throw new MessagingException('Messages can only be taken back within '.config('messaging.unsend_minutes').' minutes.');
        }
        DB::transaction(function () use ($message) {
            foreach ($message->attachments as $attachment) {
                $this->deleteFile($attachment);
                $attachment->delete();
            }
            $message->delete();
        });
    }

    public function markRead(Conversation $conversation, Party $as, User $by): void
    {
        $lastId = (int) Message::withTrashed()->where('conversation_id', $conversation->id)->max('id');
        $keys = ['conversation_id' => $conversation->id, 'user_id' => $by->id, 'party_type' => $as->type, 'party_id' => $as->id];
        $current = (int) DB::table('conversation_reads')->where($keys)->value('last_read_message_id');
        if ($lastId <= $current) {
            return;
        }
        DB::table('conversation_reads')->updateOrInsert($keys, ['last_read_message_id' => $lastId, 'updated_at' => now()]);
    }

    /** Highest message id anyone on the other side has read ("Seen"). */
    public function seenUpTo(Conversation $conversation, Party $me): int
    {
        $other = $conversation->loadMissing('participants')->otherParty($me);
        if (! $other) {
            return 0;
        }

        return (int) DB::table('conversation_reads')
            ->where('conversation_id', $conversation->id)
            ->where('party_type', $other->type)->where('party_id', $other->id)
            ->max('last_read_message_id');
    }

    public function lastReadBy(Conversation $conversation, Party $as, User $by): int
    {
        return (int) DB::table('conversation_reads')
            ->where(['conversation_id' => $conversation->id, 'user_id' => $by->id, 'party_type' => $as->type, 'party_id' => $as->id])
            ->value('last_read_message_id');
    }

    /** Unread direct messages for this person acting as this party. */
    public function unreadCount(Party $as, User $by): int
    {
        return (int) DB::table('messages as m')
            ->join('conversation_participants as p', function ($join) use ($as) {
                $join->on('p.conversation_id', '=', 'm.conversation_id')->where('p.party_type', $as->type)->where('p.party_id', $as->id);
            })
            ->leftJoin('conversation_reads as r', function ($join) use ($as, $by) {
                $join->on('r.conversation_id', '=', 'm.conversation_id')->where('r.user_id', $by->id)->where('r.party_type', $as->type)->where('r.party_id', $as->id);
            })
            ->whereNull('m.deleted_at')
            ->where(fn ($q) => $q->where('m.sender_party_type', '!=', $as->type)->orWhere('m.sender_party_id', '!=', $as->id))
            ->whereRaw('m.id > COALESCE(r.last_read_message_id, 0)')
            ->count();
    }

    /** @return Collection<int, Message> */
    public function messagesAfter(Conversation $conversation, int $afterId, int $limit = 200): Collection
    {
        return $conversation->messages()
            ->withTrashed()
            ->with(['attachments', 'author'])
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function setArchived(Conversation $conversation, Party $as, bool $archived): void
    {
        $conversation->participantFor($as)?->forceFill(['archived_at' => $archived ? now() : null])->save();
    }

    public function setMuted(Conversation $conversation, Party $as, bool $muted): void
    {
        $conversation->participantFor($as)?->forceFill(['muted_until' => $muted ? now()->addYears(10) : null])->save();
    }

    public function assertParticipant(Conversation $conversation, Party $as, User $by): void
    {
        if (! $conversation->hasParticipant($as) || ! $as->actsFor($by)) {
            abort(404);
        }
    }

    public function attachmentDisk(): string
    {
        return 'local';
    }

    public function attachmentPath(MessageAttachment $attachment): string
    {
        return $this->uploads->resolveRelativePath($attachment->stored_path);
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
        $limit = (int) config('messaging.limits.attachments_per_hour');
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

    public function deleteFile(MessageAttachment $attachment): void
    {
        Storage::disk('local')->delete($this->uploads->resolveRelativePath($attachment->stored_path));
    }
}
