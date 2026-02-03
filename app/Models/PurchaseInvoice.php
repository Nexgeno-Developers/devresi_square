<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'invoice_number','supplier_id','property_id','reference',
        'invoice_date','due_date','subtotal','tax_amount','total_amount','status_id','notes','created_by'
    ];

    public function items() {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function supplier() {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function noteApplications() {
        return $this->morphMany(NoteApplication::class, 'applied_to');
    }

    public function appliedCredits() {
        return (float) $this->noteApplications()
            ->where('note_type', CreditNote::class)
            ->sum('applied_amount');
    }

    public function appliedDebits() {
        return (float) $this->noteApplications()
            ->where('note_type', DebitNote::class)
            ->sum('applied_amount');
    }

    public function paidAmount() {
        return (float) \DB::table('transactions')
            ->where('invoice_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount WHEN transaction_type='credit' THEN -amount ELSE 0 END),0) as paid")
            ->value('paid');
    }

    public function outstandingAmount() {
        $paid = $this->paidAmount();
        $appliedDebits = $this->appliedDebits();
        $appliedCredits = $this->appliedCredits();
        $adjusted = $this->total_amount - $appliedDebits + $appliedCredits;
        return max(0, $adjusted - $paid);
    }
}