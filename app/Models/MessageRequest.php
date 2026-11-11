<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRequest extends Model
{
    use HasUlids;

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    /** Opened straight away because the two already work together. */
    public const AUTO = 'auto';

    public const DECLINED = 'declined';

    public const EXPIRED = 'expired';

    public const WITHDRAWN = 'withdrawn';

    public const BLOCKED = 'blocked';

    protected $fillable = [
        'pair_key', 'from_type', 'from_id', 'to_type', 'to_id', 'to_label', 'sent_by_user_id', 'intro', 'intro_masked',
        'status', 'attempt', 'decline_count', 'cooldown_until', 'decided_by_user_id', 'decided_at', 'expires_at', 'conversation_id',
    ];

    protected $casts = [
        'intro_masked' => 'boolean',
        'cooldown_until' => 'datetime',
        'decided_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function fromParty(): Party
    {
        return Party::make($this->from_type, (int) $this->from_id);
    }

    /** Null when the request went to an email address that matched nobody. */
    public function toParty(): ?Party
    {
        return $this->to_type === Party::EMAIL || ! $this->to_id ? null : Party::make($this->to_type, (int) $this->to_id);
    }

    public function isFrom(Party $party): bool
    {
        return $this->fromParty()->is($party);
    }

    public function isTo(Party $party): bool
    {
        return $party->is($this->toParty());
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
