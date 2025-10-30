<?php

namespace App\Exceptions\Reports;

class InvalidVoiceCallingException extends BaseException
{
    public function __construct($message)
    {
        parent::__construct('exceptions.voice_channel.calling.invalid', $message);
    }
}
