<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteRefund extends Model
{
    protected $table = 'credit_note_refunds';

    protected $fillable = [
        'credit_note_id',
        'transaction_number',
        'refund_date',
        'payment_method_id',
        'bank_account_id',
        'amount',
        'status',
        'reference',
        'notes',
        'processed_by',
    ];

    public function note()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}