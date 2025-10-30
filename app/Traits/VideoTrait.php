<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use DB;
use App\Http\Services\MasterdataService;

trait VideoTrait {

    private function getFullPath($path)
    {
        $url = rtrim(env('CONTENT_AWS_URL', 'https://localhost'));
        $path = ltrim($path);

        return "{$url}/{$path}";
    }

    private function getCacheTimeLive()
    {
        return env('CONTENT_CACHE_LIVE_TIME', static::CACHE_TIME_LIVE);
    }

    private function getUsersInfo($userIds)
    {
        $users = DB::table('users')
            ->whereIn('users.id', $userIds)
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->select('user_settings.online', 'users.*')
            ->get();

        $mapUsers = [];
        foreach ($users as $key => $value) {
            $mapUsers[$value->id] = $value;
        }

        return $mapUsers;
    }

    private function modifyUserInfo($user, $mapUsers)
    {
        if (empty($mapUsers[$user->user_id])) {
            return [];
        }

        return [
            'id'            => $mapUsers[$user->user_id]->id,
            'user_id'       => $mapUsers[$user->user_id]->id,
            'avatar'        => $mapUsers[$user->user_id]->avatar,
            'username'      => $mapUsers[$user->user_id]->username,
            'user_type'     => $mapUsers[$user->user_id]->user_type,
            'is_vip'        => $mapUsers[$user->user_id]->is_vip,
            'sex'           => $mapUsers[$user->user_id]->sex,
            'online'        => $mapUsers[$user->user_id]->online,
        ];
    }

    private function getGameInfo($gameId)
    {
        $games = MasterdataService::getOneTable('games');

        $game = collect($games)->first(function ($game) use ($gameId) {
            return $game->id === intval($gameId);
        });

        if (!$game) {
            return [];
        }

        return [
            'id'            => $game->id,
            'title'         => $game->title,
            'slug'          => $game->slug,
            'logo'          => $game->logo,
            'thumbnail'     => $game->thumbnail,
            'portrait'      => $game->portrait,
            'cover'         => $game->cover
        ];
    }
}
