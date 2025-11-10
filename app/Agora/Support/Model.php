<?php

namespace App\Agora\Support;

use App\Agora\Support\RtcTokenBuilder;
use App\Agora\Support\DynamicKey4;
use App\Agora\Support\DynamicKey5;
use Exception;

class Model
{
    const DYNAMIC_KEY_V4 = 'v4';
    const DYNAMIC_KEY_V5 = 'v5';

    private $appId;
    private $certificate;
    private $expireTimeInSeconds;

    public function __construct($appId, $certificate, $expireTimeInSeconds)
    {
        $this->appId = $appId;
        $this->certificate = $certificate;
        $this->expireTimeInSeconds = $expireTimeInSeconds;
    }

    public function generateRtcToken($channelName, $uid, $role = RtcTokenBuilder::RolePublisher)
    {
        $privilegeExpiredTs = time() + $this->expireTimeInSeconds;

        $token = RtcTokenBuilder::buildTokenWithUid(
            $this->appId, $this->certificate, $channelName, $uid, $role, $privilegeExpiredTs
        );

        return $token;
    }

    public function getDynamicKeyInstance($version = self::DYNAMIC_KEY_V4)
    {
        switch ($version) {
            case static::DYNAMIC_KEY_V4:
                return new DynamicKey4($this->appId, $this->certificate);
            case static::DYNAMIC_KEY_V5:
                return new DynamicKey5($this->appId, $this->certificate);
        }

        throw new Exception('The dynamic key have to be v4 or v5.');
    }

    public function generateMediaChannelKey($channelName, $uid, $version = self::DYNAMIC_KEY_V4)
    {
        $dynamicGenerator = $this->getDynamicKeyInstance($version);
        return $dynamicGenerator->generateMediaChannelKey($channelName, $uid, $this->expireTimeInSeconds);
    }
}
