<?php

namespace App\Exceptions\Reports;

class MissingKlaviyoKeyException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.missing_klaviyo_key');
    }
}
