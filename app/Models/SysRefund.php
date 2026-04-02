<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysRefund extends Model
{
    use HasFactory;

    protected $table = 'sys_refunds';
    protected $guarded = [];
    const UPDATED_AT = null;
}
