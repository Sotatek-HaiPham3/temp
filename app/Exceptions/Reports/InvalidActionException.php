<?php

namespace App\Exceptions\Reports;

class InvalidActionException extends BaseException {

    public function __construct($key = 'exceptions.invalid_action')
    {
        parent::__construct($key);
    }
}
