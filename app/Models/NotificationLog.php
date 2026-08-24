<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'account_id',
        'identifier',
        'notifiable_type',
        'notifiable_id',
        'subject_type',
        'subject_id',
        'actor_id',
        'notification_uuid',
        'idempotency_key',
        'channel',
        'recipient',
        'subject',
        'message',
        'payload',
        'status',
        'attempt',
        'max_attempts',
        'scheduled_for',
        'last_attempt_at',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
