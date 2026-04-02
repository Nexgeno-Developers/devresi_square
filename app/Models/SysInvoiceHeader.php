<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SysInvoiceHeader extends Model
{
    use HasFactory;

    protected $table = 'sys_invoice_headers';

    protected $guarded = [];

    public function saleInvoices(): HasMany
    {
        return $this->hasMany(SysSaleInvoice::class, 'invoice_header_id');
    }
}
