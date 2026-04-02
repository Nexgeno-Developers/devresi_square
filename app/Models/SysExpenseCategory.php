<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'sys_expense_categories';
    protected $guarded = [];
    const UPDATED_AT = null;
}
