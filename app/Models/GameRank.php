<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRank extends Model
{
    protected $fillable = [
        'game_id',
        'name'
    ];
}
