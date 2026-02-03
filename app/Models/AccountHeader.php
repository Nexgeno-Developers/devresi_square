<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHeader extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'charge_on', 'who_can_view', 'reminders',
        'agent_fees', 'require_bank_details', 'charge_in', 'can_have_duration',
        'settle_through', 'duration_parameter_required', 'penalty_type',
        'tax_included', 'tax_type', 'transaction_between'
    ];
}