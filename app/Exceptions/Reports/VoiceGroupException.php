<?php

namespace App\Exceptions\Reports;

class VoiceGroupException extends BaseException {

    public function __construct($key = 'exceptions.not_permission')
    {
        parent::__construct($key);
    }
}
