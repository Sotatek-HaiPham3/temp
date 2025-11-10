<?php

namespace App\Events;

use App\Consts;
use App\Http\Services\UserService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TaskCollected extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $userId;
    private $taskingId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $taskingId)
    {
        $this->userId   = $userId;
        $this->data = [
            'tasking_id' => $taskingId
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(Consts::SOCKET_CHANNEL_USER.$this->userId);
    }
}

