<?php

namespace App\Exceptions\Reports;

class PhoneNumberNotSupportedException extends BaseException
{
    public function __construct($key = 'exceptions.phone_not_supported')
    {
        parent::__construct($key);
    }
}
