<?php

namespace App\Exceptions\Reports;

class ChangeUsernameException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
