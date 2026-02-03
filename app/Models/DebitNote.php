<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $fillable = ['note_number','note_date','party_id','party_role','total_amount','currency','status','notes','created_by'];

    public function party() {
        return $this->belongsTo(User::class, 'party_id');
    }

    public function applications() {
        return $this->morphMany(NoteApplication::class, 'note');
    }

    public function refunds() {
        return $this->hasMany(\App\Models\DebitNoteRefund::class, 'debit_note_id');
    }

    public function appliedAmount() {
        return (float) $this->applications()->sum('applied_amount');
    }

    public function refundedAmount() {
        return (float) $this->refunds()->where('status','completed')->sum('amount');
    }

    public function remainingAmount() {
        return (float)$this->total_amount - $this->appliedAmount() - $this->refundedAmount();
    }
}