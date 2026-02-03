<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyManagerTenancy extends Model
{
    use HasFactory;

    // Define table name if it's not the plural of the model name
    protected $table = 'property_manager_tenancy';

    // Mass assignable attributes (adjust based on your requirements)
    protected $fillable = [
        'tenancy_id',
        'property_manager_id',
        'property_id',
    ];

    /**
     * Get the tenancy that owns the PropertyManagerTenancy.
     */
    public function tenancy()
    {
        return $this->belongsTo(Tenancy::class);
    }

    /**
     * Get the property manager that owns the PropertyManagerTenancy.
     */
    public function propertyManager()
    {
        return $this->belongsTo(User::class, 'property_manager_id');
    }

    /**
     * Get the property that belongs to the PropertyManagerTenancy.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
