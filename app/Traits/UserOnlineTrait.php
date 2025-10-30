<?php

namespace App\Traits;
use Cache;

trait UserOnlineTrait {

    private function storeUsersOnline($data)
    {
        $event = $data['event'];

        if (! $this->isValidChannel($event)) {
            return;
        }

        if ($event['channel'] !== $this->getUserOnlineChannel()) {
            return;
        }

        $userIds = collect($event['members'])->map(function ($item) {
            return $item['user_id'];
        })->unique()->toArray();

        $this->log($userIds);

        $this->saveToCache($userIds);
    }

    private function saveToCache($data)
    {
        $key = $this->getUsersOnlineKey();
        Cache::put($key, $data, 5); // 5 seconds;
    }

    private function isUserOnline($userId)
    {
        $key = $this->getUsersOnlineKey();
        $userIds  = Cache::has($key) ? Cache::get($key) : [];

        return in_array($userId, $userIds);
    }

    private function isValidChannel($data)
    {
        return gettype($data) === 'array' && array_key_exists('channel', $data);
    }

    private function getUsersOnlineKey()
    {
        return 'users-online';
    }

    private function getUserOnlineChannel()
    {
        return 'presence-UserOnline:members';
    }

    private function log($data)
    {
        $msg = json_encode($data);
        echo "users online: $msg";
    }
}
