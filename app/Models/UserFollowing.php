<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class UserFollowing extends Model
{
    protected $table = 'user_following';

    protected $fillable = [
        'user_id',
        'following_id',
        'is_following'
    ];

    public function idol()
    {
        return $this->hasOne('App\Models\User', 'id', 'following_id')
            ->with(['statistic', 'visibleSettings'])
            ->select('id', 'username', 'avatar', 'last_time_active', 'level', 'sex');
    }

    public function fan()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->with(['statistic', 'visibleSettings'])
            ->select('id', 'username', 'avatar', 'last_time_active', 'level', 'sex');
    }

    public static function userHasFollowerByFollowingId($userId, $followingId)
    {
        return static::where('user_id', $userId)
            ->where('following_id', $followingId)
            ->where('is_following', Consts::TRUE)
            ->exists();
    }
}
