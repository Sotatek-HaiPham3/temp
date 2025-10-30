<?php

namespace App\Exceptions\Reports;

class ClaimBountyException extends BaseException
{
    public function __construct($key)
    {
    	parent::__construct($key);
    }
}
