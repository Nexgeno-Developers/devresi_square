<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    use BelongsToSaasAccount;

    protected $fillable = [
        'account_id',
        'noteable_id',
        'noteable_type',
        'content',
        'note_type_id',
        'visibility',
        'created_by',
    ];

    public function noteType()
    {
        return $this->belongsTo(NoteType::class, 'note_type_id');
    }

    public function noteable()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
