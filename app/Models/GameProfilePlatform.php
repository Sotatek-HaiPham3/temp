<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProfilePlatform extends Model
{
    protected $fillable = [
        'game_profile_id',
        'platform_id'
    ];
}
