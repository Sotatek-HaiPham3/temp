<?php

namespace App\Exceptions\Reports;

class MobileAppRequestException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.only_mobile_app_request');
    }
}
