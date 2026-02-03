<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    // Table name (optional, if it doesn't follow Laravel's convention)
    protected $table = 'currencies';

    // Mass assignable fields
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'symbol_position',
        'exchange_rate',
        'is_default',
        'active',
    ];
}