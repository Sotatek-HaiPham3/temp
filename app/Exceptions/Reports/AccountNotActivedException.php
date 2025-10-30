<?php

namespace App\Exceptions\Reports;

class AccountNotActivedException extends BaseException
{
    public function __construct($key = 'exceptions.account_not_activated')
    {
        parent::__construct($key);
    }
}
