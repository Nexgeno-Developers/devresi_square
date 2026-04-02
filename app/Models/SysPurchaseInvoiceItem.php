<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysPurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'sys_purchase_invoice_items';
    protected $guarded = [];
    const UPDATED_AT = null;
}
