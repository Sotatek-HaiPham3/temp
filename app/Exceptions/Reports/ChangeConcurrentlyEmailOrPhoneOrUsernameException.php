<?php

namespace App\Exceptions\Reports;

class ChangeConcurrentlyEmailOrPhoneOrUsernameException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.change_concurrently_email_phone_username');
    }
}
