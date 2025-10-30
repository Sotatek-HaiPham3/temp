<?php

namespace App\Exceptions\Reports;

class ClaimBountyExistedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.claim_bounty.existed_request_claim');
    }
}
