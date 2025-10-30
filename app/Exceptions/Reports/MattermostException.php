<?php

namespace App\Exceptions\Reports;

class MattermostException extends BaseException
{
    public function __construct($key, $message = null)
    {
        parent::__construct($key, $message);
    }
}
