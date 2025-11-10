<?php

namespace App\Exceptions\Reports;

class NodebbException extends BaseException
{
    public function __construct($key, $message = null)
    {
        parent::__construct($key, $message);
    }
}
