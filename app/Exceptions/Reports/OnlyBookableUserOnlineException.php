<?php

namespace App\Exceptions\Reports;

class OnlyBookableUserOnlineException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.only_bookable_user_online');
    }
}
