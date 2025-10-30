<?php

namespace App\Exceptions\Reports;

class EmailNotVerifiedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.email_not_verified');
    }
}
