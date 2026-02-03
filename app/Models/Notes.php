<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    protected $fillable = ['noteable_id', 'noteable_type', 'content', 'note_type_id'];

    public function noteType()
    {
        return $this->belongsTo(NoteType::class, 'note_type_id');
    }

    public function noteable()
    {
        return $this->morphTo();
    }
}
