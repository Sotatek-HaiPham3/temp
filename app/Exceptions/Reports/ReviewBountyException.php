<?php

namespace App\Exceptions\Reports;

class ReviewBountyException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.bounty_not_complete');
    }
}
