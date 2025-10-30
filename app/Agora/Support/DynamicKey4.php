<?php

namespace App\Agora\Support;

class DynamicKey4 {

    const SERVICE_TYPE_ACS = 'ACS';
    const SERVICE_TYPE_ARS = 'ARS';

    private $appId;
    private $certificate;

    public function __construct($appId, $certificate)
    {
        $this->appId = $appId;
        $this->certificate = $certificate;
    }

    public function generateRecordingKey($channelName, $ts, $randomInt, $uid,
        $expiredTs ,$serviceType = self::SERVICE_TYPE_ARS)
    {
        return $this->generateDynamicKey($channelName, $ts, $randomInt, $uid,$expiredTs, $serviceType);
    }

    public function generateMediaChannelKey($channelName, $uid, $expiredTs ,$serviceType = self::SERVICE_TYPE_ACS)
    {
        $ts = time();
        $randomInt = rand(0, 100000);

        $privilegeExpiredTs = $ts + $expiredTs;
        return $this->generateDynamicKey($channelName, $ts, $randomInt, $uid, $expiredTs ,$serviceType);
    }

    // public function generateMediaChannelKey($channelName, $ts, $randomInt,$uid,
    //     $expiredTs ,$serviceType = self::SERVICE_TYPE_ACS)
    // {
    //     return $this->generateDynamicKey($channelName, $ts, $randomInt, $uid, $expiredTs ,$serviceType);
    // }

    private function generateDynamicKey($channelName, $ts, $randomInt, $uid, $expiredTs ,$serviceType)
    {
        $version = '004';

        $randomStr = '00000000' . dechex($randomInt);
        $randomStr = substr($randomStr,-8);

        $uidStr = '0000000000' . $uid;
        $uidStr = substr($uidStr,-10);
        
        $expiredStr = '0000000000' . $expiredTs;
        $expiredStr = substr($expiredStr,-10);

        $signature = $this->generateSignature($channelName, $ts, $randomStr, $uidStr, $expiredStr ,$serviceType);

        return $version . $signature . $this->appId . $ts . $randomStr . $expiredStr;
    }

    private function generateSignature($channelName, $ts, $randomStr, $uidStr, $expiredStr ,$serviceType)
    {
        $concat = $serviceType . $this->appId . $ts . $randomStr . $channelName . $uidStr . $expiredStr;
        return hash_hmac('sha1', $concat, $this->certificate);
    }
}
