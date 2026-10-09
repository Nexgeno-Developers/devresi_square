<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractorCoverageGap extends Model
{
    protected $fillable = ['account_id', 'repair_issue_id', 'postcode_district', 'trade'];
}
