<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = ['conversation_id', 'party_type', 'party_id', 'archived_at', 'muted_until'];

    protected $casts = [
        'archived_at' => 'datetime',
        'muted_until' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function party(): Party
    {
        return Party::make($this->party_type, (int) $this->party_id);
    }
}
