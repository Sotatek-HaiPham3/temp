<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class GamelancerInfo extends Model
{
    protected $fillable = [
        'user_id',
        'total_hours',
        'social_network_id',
        'game_profile_id',
        'introduction',
        'invitation_code',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User')
            ->select('id', 'email', 'username', 'audio', 'sex', 'languages', 'level', 'dob');
    }

    public function socialLink()
    {
        return $this->hasOne('App\Models\UserSocialNetwork', 'id', 'social_link_id')
            ->select('id', 'type', 'url')
            ->where('visible', Consts::TRUE);
    }

    public function gameProfile()
    {
        return $this->hasOne('App\Models\GameProfile', 'id', 'game_profile_id')
            ->with(['medias', 'game', 'platforms', 'gameOffers']);
    }
}
