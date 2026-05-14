<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffContact extends Model
{
    protected $fillable = [
        'staff_id',
        'type',   // 'email' or 'phone'
        'value',
        'label',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
