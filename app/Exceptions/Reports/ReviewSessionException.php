<?php

namespace App\Exceptions\Reports;

class ReviewSessionException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.session_not_complete');
    }
}
