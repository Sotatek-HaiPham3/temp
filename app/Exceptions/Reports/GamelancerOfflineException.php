<?php

namespace App\Exceptions\Reports;

class GamelancerOfflineException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.gamelancer_offline');
    }
}
