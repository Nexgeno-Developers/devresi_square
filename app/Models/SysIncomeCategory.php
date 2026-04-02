<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysIncomeCategory extends Model
{
    use HasFactory;

    protected $table = 'sys_income_categories';
    protected $guarded = [];
    const UPDATED_AT = null;
}
