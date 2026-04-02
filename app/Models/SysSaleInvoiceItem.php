<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysSaleInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'sys_sale_invoice_items';
    protected $guarded = [];
    const UPDATED_AT = null;
}
