<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBlock extends Model
{
    protected $fillable = ['blocker_type', 'blocker_id', 'blocked_type', 'blocked_id', 'blocked_by_user_id', 'reason'];
}
