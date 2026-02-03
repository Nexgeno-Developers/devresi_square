<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInstanceChange extends Model
{
    protected $fillable = [
        'event_instance_id',
        'changed_field',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
        'comment',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Each change belongs to one EventInstance.
     */
    public function instance()
    {
        return $this->belongsTo(EventInstance::class, 'event_instance_id');
    }

    /**
     * (Optional) If you want to link to the User who made the change:
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
