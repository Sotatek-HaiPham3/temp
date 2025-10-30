<?php

namespace App\Exceptions\Reports;

class InvalidSessionScheduleException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
