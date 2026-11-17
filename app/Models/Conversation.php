<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A chat that exists only because of a tenancy or an assigned job.
 * Created and archived by ConversationProvisioner, never by a user.
 */
class Conversation extends Model
{
    use HasUlids;

    public const ACTIVE = 'active';

    public const ARCHIVED = 'archived';

    public const KIND_TENANCY = 'tenancy';

    public const KIND_REPAIR_JOB = 'repair_job';

    public const KIND_COMPLIANCE_JOB = 'compliance_job';

    protected $fillable = [
        'kind', 'context_id', 'account_id', 'property_id', 'status', 'archived_at', 'archived_reason', 'last_message_at', 'last_message_id',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** Chats this party is currently part of (tenants who left are excluded). */
    public function scopeVisibleTo(Builder $query, Party $party): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q
            ->where('party_type', $party->type)->where('party_id', $party->id)->whereNull('left_at'));
    }

    public function hasParticipant(Party $party): bool
    {
        $participant = $this->participantFor($party);

        return $participant !== null && $participant->left_at === null;
    }

    public function participantFor(Party $party): ?ConversationParticipant
    {
        return $this->participants->first(fn (ConversationParticipant $p) => $p->party_type === $party->type && (int) $p->party_id === $party->id);
    }

    /** @return Collection<int, ConversationParticipant> */
    public function currentParticipants(): Collection
    {
        return $this->participants->whereNull('left_at')->values();
    }

    /** @return Collection<int, ConversationParticipant> */
    public function others(Party $me): Collection
    {
        return $this->currentParticipants()->reject(fn (ConversationParticipant $p) => $p->party_type === $me->type && (int) $p->party_id === $me->id)->values();
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function isJob(): bool
    {
        return in_array($this->kind, [self::KIND_REPAIR_JOB, self::KIND_COMPLIANCE_JOB], true);
    }

    public function repairOffer(): ?RepairJobOffer
    {
        return $this->kind === self::KIND_REPAIR_JOB ? RepairJobOffer::query()->find($this->context_id) : null;
    }

    public function complianceOffer(): ?ComplianceJobOffer
    {
        return $this->kind === self::KIND_COMPLIANCE_JOB ? ComplianceJobOffer::query()->find($this->context_id) : null;
    }

    public function tenancy(): ?Tenancy
    {
        return $this->kind === self::KIND_TENANCY ? Tenancy::query()->find($this->context_id) : null;
    }
}
