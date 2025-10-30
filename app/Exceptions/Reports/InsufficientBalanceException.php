<?php

namespace App\Exceptions\Reports;

class InsufficientBalanceException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.insufficient_balance');
    }
}
