<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostcodeLocation extends Model
{
    protected $fillable = ['postcode', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
