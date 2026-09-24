<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyBreachLog extends Model
{
    protected $fillable = [
        'account_id',
        'severity',
        'status',
        'summary',
        'details',
        'discovered_at',
        'contained_at',
        'notified_at',
        'recorded_by',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'contained_at' => 'datetime',
        'notified_at' => 'datetime',
    ];
}
