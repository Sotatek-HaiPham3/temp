<?php

namespace App\Traits;

use App\Jobs\SendSystemNotification;
use App\Consts;

trait NotificationTrait {

    public function fireNotification($type, $params)
    {
        SendSystemNotification::dispatch($type, $params)->onQueue(Consts::QUEUE_NOTIFICATION);
    }
}
