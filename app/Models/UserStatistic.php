<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'total_reviewers',
        'session_rating',
        'session_reviewers',
        'total_followers',
        'total_following',
        'response_time',
        'session_rating',
        'session_reviewers',
        'session_played'
    ];
}
