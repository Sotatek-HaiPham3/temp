<?php

namespace App\Exceptions\Reports;

class EmailVerifiedException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.email_verified');
    }
}
