<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['account_id', 'user_id', 'event_key', 'email_enabled', 'in_app_enabled'];

    protected $casts = [
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
