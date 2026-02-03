<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'code', 'name', 'zone_id', 'status'];
    
    public static function allCached()
    {
        return Cache::rememberForever('countries.all', function () {
            // Uncomment the line below to return all countries
            return self::all();

            // Return only active countries
            // return self::where('status', 1)->get();
        });
    }

    public static function clearCache()
    {
        Cache::forget('countries.all');
    }

    protected static function booted()
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });

        static::restored(function () {
            static::clearCache();
        });
    }
}
