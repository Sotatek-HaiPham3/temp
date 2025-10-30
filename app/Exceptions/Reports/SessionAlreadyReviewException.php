<?php

namespace App\Exceptions\Reports;

class SessionAlreadyReviewException extends BaseException
{
    public function __construct()
    {
        parent::__construct('validation.session_already_review');
    }
}
