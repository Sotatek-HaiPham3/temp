<?php

namespace App\Http\Middleware\Supports;

use Illuminate\Session\CacheBasedSessionHandler as BaseCacheBasedSessionHandler;
use App\Traits\UserSessionTrait;

class CacheBasedSessionHandler extends BaseCacheBasedSessionHandler
{

    use UserSessionTrait;

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        if (!$this->shouldUpdateCache($sessionId, $data)) {
            return false;
        }

        $data = $this->appendUserInfo($data);

        return $this->cache->put($sessionId, $data, $this->minutes * 60);
    }

    private function shouldUpdateCache($sessionId, $newData)
    {
        $current = $this->parseData(
            $this->read($sessionId)
        );

        $new = $this->parseData($newData);

        return !$this->isSamePayload($current, $new);
    }

    private function isSamePayload($current, $new)
    {
        // $currentPayload = empty($current['_user']) ? [] : $current['_user'];
        // unset($currentPayload['ip_address']);

        // $newPayload = $this->getCurrentUser();
        // unset($newPayload['ip_address']);

        // return $this->isEqual($currentPayload, $newPayload);
        return $current === $new;
    }

    // private function isEqual($source, $target)
    // {
    //     ksort_recursive($source);
    //     ksort_recursive($target);

    //     return base64_encode(json_encode($source)) === base64_encode(json_encode($target));
    // }

    private function appendUserInfo($data)
    {
        $data = $this->parseData($data);

        // $data['_user'] = $this->getCurrentUser();
        $data = $this->getCurrentUser()['id'];

        return $data;
    }

    private function parseData($data)
    {
        if (empty($data)) {
            return [];
        }

        if (gettype($data) === 'string') {
            return @unserialize($data);
        }

        return $data;
    }
}
