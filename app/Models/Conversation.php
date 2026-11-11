<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasUlids;

    public const ACTIVE = 'active';

    public const READ_ONLY = 'read_only';

    public const ORIGIN_REQUEST = 'request';

    public const ORIGIN_TENANCY = 'tenancy';

    public const ORIGIN_REPAIR_JOB = 'repair_job';

    public const ORIGIN_COMPLIANCE_JOB = 'compliance_job';

    public const ORIGIN_EVENT = 'event';

    protected $fillable = [
        'pair_key', 'request_id', 'origin', 'origin_id', 'status', 'read_only_reason', 'unmasked_at', 'last_message_at', 'last_message_id',
    ];

    protected $casts = [
        'unmasked_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MessageRequest::class, 'request_id');
    }

    public function scopeVisibleTo(Builder $query, Party $party): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->where('party_type', $party->type)->where('party_id', $party->id));
    }

    public function hasParticipant(Party $party): bool
    {
        return $this->participantFor($party) !== null;
    }

    public function participantFor(Party $party): ?ConversationParticipant
    {
        return $this->participants->first(fn (ConversationParticipant $p) => $p->party_type === $party->type && (int) $p->party_id === $party->id);
    }

    public function otherParty(Party $me): ?Party
    {
        $other = $this->participants->first(fn (ConversationParticipant $p) => ! ($p->party_type === $me->type && (int) $p->party_id === $me->id));

        return $other ? Party::make($other->party_type, (int) $other->party_id) : null;
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }
}
