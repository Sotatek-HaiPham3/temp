<?php

namespace App\Exceptions\Reports;

class UserNotExitstException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.user_not_exitst');
    }
}
