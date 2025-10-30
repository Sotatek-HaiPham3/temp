<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRestrictPricing extends Model
{
    protected $table = 'user_restrict_pricings';

    protected $fillable = [
        'user_id',
        'min',
        'max'
    ];

    public static function userRestrictPricingExists($userId)
    {
        return static::where('user_id', $userId)->exists();
    }
}
