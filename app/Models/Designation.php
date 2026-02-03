<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['title'];  // The fields we want to allow mass assignment

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
