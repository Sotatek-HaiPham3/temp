<?php

namespace App\Exceptions\Reports;

class TestException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.invalid_test');
    }
}
