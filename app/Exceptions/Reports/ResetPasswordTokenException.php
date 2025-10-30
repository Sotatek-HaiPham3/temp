<?php

namespace App\Exceptions\Reports;

class ResetPasswordTokenException extends BaseException
{
    public function __construct()
    {
        parent::__construct('passwords.token');
    }
}
