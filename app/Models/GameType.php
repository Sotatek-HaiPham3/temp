<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameType extends Model
{
    protected $fillable = [
        'game_id',
        'type',
        'is_active'
    ];
}
