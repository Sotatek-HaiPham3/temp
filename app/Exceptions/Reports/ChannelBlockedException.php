<?php

namespace App\Exceptions\Reports;

class ChannelBlockedException extends BaseException
{
    public function __construct($key = 'exceptions.channel_blocked')
    {
        parent::__construct($key);
    }
}
