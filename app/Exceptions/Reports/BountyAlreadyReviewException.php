<?php

namespace App\Exceptions\Reports;

class BountyAlreadyReviewException extends BaseException
{
    public function __construct()
    {
        parent::__construct('validation.bounty_already_review');
    }
}
