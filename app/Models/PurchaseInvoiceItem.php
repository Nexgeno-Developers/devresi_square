<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = ['purchase_invoice_id','title','description','unit_price','quantity','total_price','tax_rate','tax_rate_id'];

    public function purchaseInvoice() {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
