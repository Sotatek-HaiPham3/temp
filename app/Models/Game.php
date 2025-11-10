<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class Game extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'logo',
        'prioritize',
        'thumbnail',
        'thumbnail_hover',
        'thumbnail_active',
        'banner',
        'portrait',
        'cover',
        'description',
        'genre',
        'order',
        'auto_order'
    ];

    public function ranks()
    {
        return $this->hasMany('App\Models\GameRank')
            ->select('id', 'game_id', 'name');
    }

    public function servers()
    {
        return $this->hasMany('App\Models\GameServer')
            ->select('id', 'game_id', 'name');
    }

    public function types()
    {
        return $this->hasMany('App\Models\GameType')
            ->select('id', 'game_id', 'type', 'is_active');
    }

    public function available_types() {
        return $this->types()->where('is_active','=', 1)
            ->select('id', 'game_id', 'type');
    }

    public function platforms()
    {
        return $this->hasMany('App\Models\GamePlatform')
            ->select('id', 'game_id', 'platform_id');
    }

    public function gameProfiles()
    {
        return $this->hasMany('App\Models\GameProfile')
            ->select('id', 'game_id')
            ->where('is_active', Consts::TRUE);
    }
}
