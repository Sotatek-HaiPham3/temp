<?php

namespace App\Exceptions\Reports;

class InvalidDataException extends BaseException
{
    public function __construct($key = 'exceptions.invalid_data')
    {
        parent::__construct($key);
    }
}
