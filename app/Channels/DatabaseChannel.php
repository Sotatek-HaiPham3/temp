<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;

class DatabaseChannel extends IlluminateDatabaseChannel
{
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        $data = $this->getData($notifiable, $notification);
        return [
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,

            // Customize
            'receiver_id' => $data['receiver_id']
        ];
    }
}
