<?php

namespace App\Exceptions\Reports;

class InvalidCredentialException extends BaseException
{
    public function __construct($message = null)
    {
        parent::__construct('invalid_credential', $message);
    }
}
