<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    public const LANDLORD = 'landlord';

    public const TENANT = 'tenant';

    public const CONTRACTOR = 'contractor';

    protected $fillable = ['conversation_id', 'party_type', 'party_id', 'role', 'left_at'];

    protected $casts = [
        'left_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function party(): Party
    {
        return Party::make($this->party_type, (int) $this->party_id);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::LANDLORD => $this->party()->roleLabel(),
            self::CONTRACTOR => 'Contractor',
            default => 'Tenant',
        };
    }
}
