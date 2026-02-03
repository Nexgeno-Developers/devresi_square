<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteApplication extends Model
{
    protected $table = 'note_applications';

    protected $fillable = [
        'note_id','note_type','applied_to_id','applied_to_type',
        'applied_amount','applied_by','applied_at'
    ];

    public function note() {
        return $this->morphTo();
    }

    public function appliedTo() {
        return $this->morphTo();
    }

    public function applier() {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
