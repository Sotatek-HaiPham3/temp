<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Session;
use App\Consts;

class GameProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'game_id',
        // 'rank_id',
        'title',
        'audio',
        'description',
        'is_active'
    ];

    public function medias()
    {
        return $this->hasMany('App\Models\GameProfileMedia')
            ->select('id', 'game_profile_id', 'url', 'type');
    }

    // public function matchServers()
    // {
    //     return $this->hasMany('App\Models\GameProfileMatchServer')
    //         ->select('game_profile_id', 'game_server_id');
    // }

    public function gameOffers()
    {
        return $this->hasMany('App\Models\GameProfileOffer')
            ->select('game_profile_id', 'type', 'quantity', 'price');
    }

    public function platforms()
    {
        return $this->hasMany('App\Models\GameProfilePlatform')
            ->select('game_profile_id', 'platform_id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\SessionReview')
            ->select('game_profile_id', 'reviewer_id', 'user_id', 'rate', 'description')
            ->where('user_id', $this->user_id)
            ->orderBy('id', 'desc')
            ->with(['userReview']);
    }

    public function availableTimes()
    {
        return $this->hasMany('App\Models\GamelancerAvailableTime', 'user_id', 'user_id')
            ->select('user_id', 'weekday', 'from', 'to', 'all');
    }

    public function userPhotos()
    {
        return $this->hasMany('App\Models\UserPhoto', 'user_id', 'user_id')
            ->select('id', 'user_id', 'type', 'url')
            ->where('is_active', Consts::TRUE);
    }

    public function userSocialLink()
    {
        return $this->hasMany('App\Models\UserSocialNetwork', 'user_id', 'user_id')
            ->select('id', 'user_id', 'type', 'url')
            ->where('visible', Consts::TRUE);
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User')
            ->select('id', 'avatar', 'username', 'audio', 'sex', 'last_time_active', 'languages', 'is_vip', 'description', 'user_type')
            ->with(['settings', 'statistic', 'personality', 'visibleSettings', 'userRanking']);
    }

    public function statistic()
    {
        return $this->hasOne('App\Models\GameProfileStatistic')
            ->select('game_profile_id', 'total_played', 'recommend', 'total_review', 'rating');
    }

    public function userRanking()
    {
        return $this->hasOne('App\Models\UserRanking', 'user_id', 'user_id');
    }

    public function game()
    {
        return $this->belongsTo('App\Models\Game')
            ->select('id', 'title', 'slug', 'logo', 'thumbnail', 'portrait', 'cover', 'is_active', 'ios_app_id', 'android_app_id');
    }

    public function canDeleteGameprofile()
    {
        return !Session::where('game_profile_id', $this->id)
                    ->whereIn('status', [
                        Consts::SESSION_STATUS_BOOKED,
                        Consts::SESSION_STATUS_ACCEPTED,
                        Consts::SESSION_STATUS_STARTING,
                        Consts::SESSION_STATUS_RUNNING
                    ])
                    ->exists();
    }
}
