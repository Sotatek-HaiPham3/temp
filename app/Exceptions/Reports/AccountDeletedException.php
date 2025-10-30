<?php

namespace App\Exceptions\Reports;

class AccountDeletedException extends BaseException
{
    public function __construct($message = null)
    {
        parent::__construct('account_deleted', $message);
    }
}
