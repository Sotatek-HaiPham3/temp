<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class GamePlatform extends Model
{
    protected $fillable = [
        'game_id',
        'platform_id'
    ];
}
