<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasUlids;

    public const TEXT = 'text';

    public const SYSTEM = 'system';

    protected $fillable = [
        'conversation_id', 'sender_party_type', 'sender_party_id', 'user_id', 'body', 'kind',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isSystem(): bool
    {
        return $this->kind === self::SYSTEM;
    }

    public function senderParty(): ?Party
    {
        return $this->sender_party_type ? Party::make($this->sender_party_type, (int) $this->sender_party_id) : null;
    }

    public function sentBy(Party $party): bool
    {
        return $this->senderParty()?->is($party) ?? false;
    }
}
