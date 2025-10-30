<?php

namespace App\Events;

use App\Consts;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityPostUnpinAllMessage extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $communityId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($communityId)
    {
        $this->communityId = $communityId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(Consts::SOCKET_PRIVATE_COMMUNITY . $this->communityId);
    }
}
