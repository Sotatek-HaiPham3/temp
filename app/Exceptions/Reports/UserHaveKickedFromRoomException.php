<?php

namespace App\Exceptions\Reports;

class UserHaveKickedFromRoomException extends BaseException
{
    public function __construct($key = 'exceptions.user_have_kicked_from_room')
    {
        parent::__construct($key);
    }
}
