<?php

namespace App\Traits;

use Illuminate\Support\Facades\Redis;

trait RedisTrait {

    private static function saveToCache($key, $data)
    {
        Redis::connection(static::getRedisConnection())->set($key, static::serialize($data));
    }

    private static function setExpire($key, $time)
    {
        Redis::connection(static::getRedisConnection())->expire($key, $time);
    }

    private static function keys($key)
    {
        return Redis::connection(static::getRedisConnection())->keys($key);
    }

    private static function deleteCacheWithKey($key)
    {
        return Redis::connection(static::getRedisConnection())->del($key);
    }

    private static function getFromCache($key)
    {
        $value = Redis::connection(static::getRedisConnection())->get($key);

        if (is_null($value)) {
            return null;
        }

        return @unserialize($value) ? static::unserialize($value) : json_decode($value);
    }

    private static function hasKeyInCache($key)
    {
        $keys = Redis::connection(static::getRedisConnection())->keys($key);
        return ! empty($keys);
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private static function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private static function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
