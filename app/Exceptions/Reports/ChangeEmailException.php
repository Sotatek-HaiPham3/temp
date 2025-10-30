<?php

namespace App\Exceptions\Reports;

class ChangeEmailException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
