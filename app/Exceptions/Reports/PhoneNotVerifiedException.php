<?php

namespace App\Exceptions\Reports;

class PhoneNotVerifiedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.phone_not_verified');
    }
}
