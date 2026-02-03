<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteType extends Model
{
    protected $fillable = ['name'];

    public function notes()
    {
        return $this->hasMany(Notes::class, 'note_type_id');
    }
}
