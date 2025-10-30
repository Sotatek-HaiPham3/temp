<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProfileMedia extends Model
{
    protected $table = 'game_profile_medias';

    protected $fillable = [
        'game_profile_id',
        'url',
        'type'
    ];
}
