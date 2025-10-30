<?php

namespace App\Exceptions\Reports;

class CommunityException extends BaseException
{
    public function __construct($key)
    {
        parent::__construct($key);
    }
}
