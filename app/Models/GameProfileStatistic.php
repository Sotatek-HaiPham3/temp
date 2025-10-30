<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProfileStatistic extends Model
{
    protected $fillable = [
        'game_profile_id',
        'total_played',
        'game_played',
        'hour_played',
        'recommend',
        'unrecommend',
        'total_review',
        'rating',
        'executed_date'
    ];
}
