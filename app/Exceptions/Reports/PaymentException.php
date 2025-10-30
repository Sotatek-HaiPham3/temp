<?php

namespace App\Exceptions\Reports;


class PaymentException extends BaseException
{
    public function __construct($key, $message = null)
    {
        parent::__construct($key, $message);
    }
}
