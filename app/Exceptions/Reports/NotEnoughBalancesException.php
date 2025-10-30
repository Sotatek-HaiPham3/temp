<?php

namespace App\Exceptions\Reports;

class NotEnoughBalancesException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.not_enough_balances');
    }
}
