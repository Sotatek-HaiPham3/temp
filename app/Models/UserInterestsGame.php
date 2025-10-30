<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInterestsGame extends Model
{
    protected $table = 'user_interests_games';

    protected $fillable = [
        'user_id',
        'game_id',
        'platform_id',
        'game_name'
    ];

    protected $appends = ['serverIds'];

    public function getServerIdsAttribute()
    {
        return $this->userInterestsGameMatchServer()->pluck('game_server_id');
    }

    public function updateData($input)
    {
        $fields = $this->attributesToArray();

        foreach ($fields as $field) {
            $value = array_get($input, $field, null);
            if (!$value) {
                continue;
            }

            $this->$field = $value;
        }

        $this->save();

        return $this;
    }

    public function userInterestsGameMatchServer()
    {
        return $this->hasMany('App\Models\UserInterestsGameMatchServer');
    }

    public function game()
    {
        return $this->hasOne('App\Models\Game');
    }

    public function createOrUpdateUserInterestsGameMatchServers($serverIds)
    {
        if (collect($serverIds)->isEmpty()) {
            return;
        }
        $this->userInterestsGameMatchServer()->delete();

        $data = collect($serverIds)->map(function ($serverId) {
            return ['game_server_id' => $serverId];
        });

        $this->userInterestsGameMatchServer()->createMany($data);

        return $this;
    }
}
