<?php

namespace App\Events;

use App\Consts;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CommunityUserKicked extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $communityId;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->communityId = $data->community_id;;
        $this->data = cloneDeep($data);
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
