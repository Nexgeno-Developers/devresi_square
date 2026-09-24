<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairSlaEvent extends Model
{
    protected $fillable = [
        'repair_issue_id',
        'event',
        'occurred_at',
        'actor_id',
        'note',
        'payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];

    public function repairIssue(): BelongsTo
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
