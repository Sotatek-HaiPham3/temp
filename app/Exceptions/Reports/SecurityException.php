<?php

namespace App\Exceptions\Reports;

class SecurityException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
