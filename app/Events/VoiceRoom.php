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

class VoiceRoom extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // private $userId;
    private $roomId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId)
    {
        // $this->userId     = $userId;
        $this->roomId = cloneDeep($roomId);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('VoiceRoom' . $this->roomId);
    }
}
