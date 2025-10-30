<?php

namespace App\Utils;

use App\Traits\RedisTrait;
use Illuminate\Support\Facades\Redis;
use App\Models\UserSetting;

class UserOnlineUtils {

    use RedisTrait;

    public static function isUserOnline($userId)
    {
        $userIds = static::getUserIdOnlines();
        $isOnlineSettings = UserSetting::where('id', $userId)->value('online');
        return $isOnlineSettings && in_array($userId, $userIds);
    }

    public static function getUserIdOnlines()
    {
        $userOnlines = static::getAllUserOnlines();
        return collect($userOnlines)->pluck('user_id')->toArray();
    }

    public static function getAllUserOnlines()
    {
        $key = static::getUserOnlineKey();

        if (static::hasKeyInCache($key)) {
            $userOnlines = static::getFromCache($key);
            return collect($userOnlines)->unique('user_id')->toArray();
        }

        return [];
    }

    private static function getUserOnlineKey()
    {
        return 'presence-UserOnline:members';
    }

    private static function getRedisConnection()
    {
        return 'default';
    }
}
