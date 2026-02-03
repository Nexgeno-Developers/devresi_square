<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenancySubStatus extends Model
{
    /** @use HasFactory<\Database\Factories\TenancySubStatusFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function tenancies()
    {
        return $this->hasMany(Tenancy::class);
    }
}
