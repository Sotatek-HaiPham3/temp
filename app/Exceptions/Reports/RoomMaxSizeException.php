<?php

namespace App\Exceptions\Reports;

class RoomMaxSizeException extends BaseException {

    public function __construct()
    {
        parent::__construct('exceptions.room_max_size');
    }
}
