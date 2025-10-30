<?php

namespace App\Exceptions\Reports;

class ChangePhoneNumberException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
