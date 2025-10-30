<?php

namespace App\Exceptions\Reports;

class InvalidTimeRangeException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.invalid_time_range');
    }
}
