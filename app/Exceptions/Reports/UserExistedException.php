<?php

namespace App\Exceptions\Reports;

class UserExistedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('existed_user');
    }
}
