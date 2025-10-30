<?php

namespace App\Exceptions\Reports;

class InvalidSessionNowException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
