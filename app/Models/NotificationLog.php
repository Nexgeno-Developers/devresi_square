<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'identifier',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'recipient',
        'subject',
        'message',
        'payload',
        'status',
        'attempt',
        'max_attempts',
        'last_attempt_at',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}

