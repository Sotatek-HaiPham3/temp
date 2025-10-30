<?php

namespace App\Http\Middleware\Supports;

use Illuminate\Session\DatabaseSessionHandler as BaseDatabaseSessionHandler;
use App\Traits\UserSessionTrait;

class DatabaseSessionHandler extends BaseDatabaseSessionHandler
{

    use UserSessionTrait;

    /**
     * Get the currently authenticated user's ID.
     *
     * @return mixed
     */
    protected function userId()
    {
        return $this->getUserGuard()->user()->id;
    }

    /**
     * Get the IP address for the current request.
     *
     * @return string
     */
    protected function ipAddress()
    {
        return getOriginalClientIp();
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $session = $this->getQuery()->find($sessionId);

        // $data = $this->appendSessionData($data, '_user', $this->getCurrentUser());

        // $payload = $this->getDefaultPayload($data);

        // if ($this->isSamePayload($session, $payload)) {
        //     return $this->exists = true;
        // }

        $payload = $this->getDefaultPayload($this->userId());

        if (!empty($session) && $session->payload === $payload['payload']) {
            return $this->exists = true;
        }

        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }

        return $this->exists = true;
    }

    // private function isSamePayload($currentData, $newData)
    // {
    //     $newData = json_decode(json_encode($newData), true);
    //     $currentData = json_decode(json_encode($currentData), true);

    //     // unset($currentData['id']);
    //     // unset($currentData['last_activity']);
    //     // unset($currentData['ip_address']);
    //     // $currentData['payload'] = empty($currentData['payload']) ? [] : $this->parseData($currentData['payload']);
    //     $currentPayload = empty($currentData['payload']) ? [] : $this->parseData($currentData['payload']);
    //     unset($currentPayload['ip_address']);

    //     // unset($newData['last_activity']);
    //     // unset($newData['ip_address']);
    //     // $newData['payload'] = $this->parseData($newData['payload']);
    //     $newPayload = $this->parseData($newData['payload']);
    //     unset($newPayload['ip_address']);

    //     return $this->isEqual($newPayload, $currentPayload);
    // }

    // private function appendSessionData($data, $key, $value)
    // {
    //     $data = unserialize($data);

    //     $data[$key] = $value;

    //     return serialize($data);
    // }

    // private function parseData($data)
    // {
    //     $data = @unserialize(base64_decode($data));

    //     return empty($data) ? [] : $data['_user'];
    // }

    // private function isEqual($source, $target)
    // {
    //     ksort_recursive($source);
    //     ksort_recursive($target);

    //     return base64_encode(json_encode($source)) === base64_encode(json_encode($target));
    // }
}
