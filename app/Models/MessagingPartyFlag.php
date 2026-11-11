<?php

namespace App\Models;

use App\Support\Messaging\Party;
use Illuminate\Database\Eloquent\Model;

class MessagingPartyFlag extends Model
{
    protected $fillable = ['party_type', 'party_id', 'paused_until', 'last_paused_at', 'pause_count', 'suspended_until', 'suspended_reason'];

    protected $casts = [
        'paused_until' => 'datetime',
        'last_paused_at' => 'datetime',
        'suspended_until' => 'datetime',
    ];

    /** "Until Super Admin reviews it". MySQL TIMESTAMP stops in January 2038. */
    public static function untilReviewed(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::create(2037, 12, 31, 0, 0, 0);
    }

    public static function for(Party $party): ?self
    {
        return self::query()->where('party_type', $party->type)->where('party_id', $party->id)->first();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_until !== null && $this->suspended_until->isFuture();
    }

    public function isPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->isFuture();
    }
}
