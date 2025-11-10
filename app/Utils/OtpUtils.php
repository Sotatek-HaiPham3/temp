<?php

namespace App\Utils;

use Cache;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\CurrencyExchange;

class OtpUtils
{
    const EXPRIED_TIME = 5 * 60; //seconds

    public static function initOtpCodeToCache($user, $otpCode)
    {
        static::saveToCache($user, $otpCode);
    }

    public static function resetOtpCodeToCache($user, $otpCode)
    {
        static::initOtpCodeToCache($user, $otpCode);
    }

    public static function confirmOtpCode($user, $confimationCode)
    {
        $otpCode = static::getCacheData($user);
        if ($otpCode === $confimationCode) {
            static::removeCacheData($user);
            return true;
        }
        return false;
    }
    
    private static function saveToCache($user, $confimationCode)
    {
        $key = static::getKey($user->id);
        static::removeCacheData($user);
        Cache::add($key, $confimationCode, static::EXPRIED_TIME);
        return static::getCacheData($user);
    }

    private static function getCacheData($user)
    {
        $key = static::getKey($user->id);

        $cacheData = null;
        if (Cache::has($key)) {
            $cacheData = Cache::get($key);
        }

        return $cacheData;
    }

    private static function removeCacheData($user)
    {
        $key = static::getKey($user->id);

        if (Cache::has($key)) {
            Cache::forget($key);
        }
    }

    private static function getKey($id)
    {
        return `OTPCode_via_user_{$id}`;
    }
}
