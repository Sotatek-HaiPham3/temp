<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBalance extends Model
{
    protected $fillable = [
        'user_id',
        'coin',
        'bar'
    ];

    public static function isEnoughBalance($userId, $coin)
    {
        return static::where('id', $userId)
                ->where('coin', '>=', $coin)
                ->exists();
    }
}
