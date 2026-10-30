<?php

namespace App\Models;

class Tenant extends User
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'pending_email',
        'phone',
        'password',
        'status',
        'can_login',
        'selected_properties',
        'created_by',
    ];
}
