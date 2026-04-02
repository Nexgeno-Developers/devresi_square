<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysAdjustmentNote extends Model
{
    use HasFactory;

    protected $table = 'sys_adjustment_notes';
    protected $guarded = [];
    const UPDATED_AT = null;
}
