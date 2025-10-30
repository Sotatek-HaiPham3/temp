<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProfileMatchServer extends Model
{
    protected $fillable = [
        'game_profile_id',
        'game_server_id'
    ];
}
