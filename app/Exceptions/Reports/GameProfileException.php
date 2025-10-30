<?php

namespace App\Exceptions\Reports;

class GameProfileException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
