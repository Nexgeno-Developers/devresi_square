<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $fillable = [
        'account_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}
