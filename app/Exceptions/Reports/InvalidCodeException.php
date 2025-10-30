<?php

namespace App\Exceptions\Reports;

class InvalidCodeException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.invalid_code');
    }
}
