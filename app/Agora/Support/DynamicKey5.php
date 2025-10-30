<?php
namespace App\Agora\Support;

class DynamicKey5 {

    const VERSION                   = '005';
    const NO_UPLOAD                 = '0';
    const AUDIO_VIDEO_UPLOAD        = '3';

    // InChannelPermissionKey
    const ALLOW_UPLOAD_IN_CHANNEL   = 1;

    // Service Type
    const MEDIA_CHANNEL_SERVICE     = 1;
    const RECORDING_SERVICE         = 2;
    const PUBLIC_SHARING_SERVICE    = 3;
    const IN_CHANNEL_PERMISSION     = 4;

    private $appId;
    private $certificate;

    public function __construct($appId, $certificate)
    {
        $this->appId = $appId;
        $this->certificate = $certificate;
    }

    public function generateRecordingKey($channelName, $ts, $randomInt, $uid, $expiredTs)
    {
        return $this->generateDynamicKey(
            $channelName, $ts, $randomInt, $uid, $expiredTs, static::RECORDING_SERVICE
        );
    }

    public function generateMediaChannelKey($channelName, $uid, $expiredTs)
    {
        $ts = time();
        $randomInt = rand(0, 100000);

        $privilegeExpiredTs = $ts + $expiredTs;

        return $this->generateDynamicKey(
            $channelName, $ts, $randomInt, $uid, $privilegeExpiredTs, static::MEDIA_CHANNEL_SERVICE
        );
    }

    // public function generateMediaChannelKey($channelName, $ts, $randomInt, $uid, $expiredTs)
    // {
    //     return $this->generateDynamicKey(
    //         $channelName, $ts, $randomInt, $uid, $expiredTs, static::MEDIA_CHANNEL_SERVICE
    //     );
    // }

    public function generateInChannelPermissionKey($channelName, $ts, $randomInt, $uid, $expiredTs, $permission)
    {
        $extra[static::ALLOW_UPLOAD_IN_CHANNEL] = $permission;
        return $this->generateDynamicKey(
            $channelName, $ts, $randomInt, $uid, $expiredTs, static::IN_CHANNEL_PERMISSION, $extra
        );
    }

    private function generateDynamicKey($channelName, $ts, $randomInt, $uid, $expiredTs, $serviceType, $extra = [])
    {
        $signature = $this->generateSignature($serviceType, $channelName, $uid, $ts, $randomInt, $expiredTs, $extra);

        $content = $this->packContent($serviceType, $signature, $ts, $randomInt, $expiredTs, $extra);

        // echo bin2hex($content);
        return static::VERSION . base64_encode($content);
    }

    private function generateSignature($serviceType, $channelName, $uid, $ts, $salt, $expiredTs, $extra)
    {
        $rawAppID = hex2bin($this->appId);
        $rawAppCertificate = hex2bin($this->certificate);
        
        $buffer = pack('S', $serviceType);
        $buffer .= pack('S', strlen($rawAppID)) . $rawAppID;
        $buffer .= pack('I', $ts);
        $buffer .= pack('I', $salt);
        $buffer .= pack('S', strlen($channelName)) . $channelName;
        $buffer .= pack('I', $uid);
        $buffer .= pack('I', $expiredTs);

        $buffer .= pack('S', count($extra));
        foreach ($extra as $key => $value) {
            $buffer .= pack('S', $key);
            $buffer .= pack('S', strlen($value)) . $value;
        } 

        return strtoupper(hash_hmac('sha1', $buffer, $rawAppCertificate));
    }

    private function packContent($serviceType, $signature, $ts, $salt, $expiredTs, $extra)
    {
        $buffer = pack('S', $serviceType);
        $buffer .= $this->packString($signature);
        $buffer .= $this->packString($this->appId);
        $buffer .= pack('I', $ts);
        $buffer .= pack('I', $salt);
        $buffer .= pack('I', $expiredTs);

        $buffer .= pack('S', count($extra));
        foreach ($extra as $key => $value) {
            $buffer .= pack('S', $key);
            $buffer .= $this->packString($value);
        } 

        return $buffer;
    }

    private function packString($value)
    {
        return pack('S', strlen($value)) . $value;
    }
}
