<?php

namespace App\Exceptions\Reports;

class InvalidVoiceChannelException extends BaseException
{
    public function __construct($key = 'exceptions.voice_channel.invalid')
    {
        parent::__construct($key);
    }
}
