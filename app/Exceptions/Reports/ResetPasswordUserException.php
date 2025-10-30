<?php

namespace App\Exceptions\Reports;

class ResetPasswordUserException extends BaseException
{
    public function __construct()
    {
        parent::__construct('passwords.user');
    }
}
