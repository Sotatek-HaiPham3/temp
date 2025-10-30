<?php

namespace App\Exceptions\Reports;

class InvalidCodeVerificationException extends BaseException {

    public function __construct($key)
    {
        parent::__construct($key);
    }
}
