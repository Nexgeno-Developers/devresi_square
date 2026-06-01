<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'demo_date',
        'demo_time',
        'hear_about',
        'subscribe',
        'attachment',
        'ip',
        'ip_data',
        'ref_url',
        'email_sent',
        'w_countrycode',
        'w_phone',
        'wati_response',
    ];

    protected $casts = [
        'subscribe' => 'boolean',
        'email_sent' => 'boolean',
        'demo_date' => 'date',
        'demo_time' => 'datetime:H:i',
    ];
}
