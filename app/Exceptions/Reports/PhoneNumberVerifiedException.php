<?php

namespace App\Exceptions\Reports;

class PhoneNumberVerifiedException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.phone_number_verified');
    }
}
