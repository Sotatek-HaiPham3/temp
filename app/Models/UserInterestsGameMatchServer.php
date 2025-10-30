<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInterestsGameMatchServer extends Model
{
    protected $table = 'user_interests_game_match_servers';

    protected $fillable = [
        'user_interests_game_id',
        'game_server_id'
    ];
}
