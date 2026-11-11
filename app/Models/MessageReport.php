<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReport extends Model
{
    use HasUlids;

    public const CATEGORIES = ['spam', 'harassment', 'scam', 'other'];

    public const OPEN = 'open';

    public const ACTIONED = 'actioned';

    public const DISMISSED = 'dismissed';

    protected $fillable = [
        'reporter_user_id', 'reporter_party_type', 'reporter_party_id', 'reported_party_type', 'reported_party_id',
        'conversation_id', 'message_id', 'request_id', 'category', 'note', 'status', 'handled_by', 'handled_at', 'outcome', 'outcome_note',
    ];

    protected $casts = ['handled_at' => 'datetime'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class)->withTrashed();
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MessageRequest::class, 'request_id');
    }

    public function reportedParty(): ?Party
    {
        return $this->reported_party_type ? Party::make($this->reported_party_type, (int) $this->reported_party_id) : null;
    }

    public function reporterParty(): Party
    {
        return Party::make($this->reporter_party_type, (int) $this->reporter_party_id);
    }
}
