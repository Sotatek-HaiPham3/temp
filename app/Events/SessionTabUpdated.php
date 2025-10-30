<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Consts;
use App\Http\Services\ChatService;

class SessionTabUpdated extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $objectId, $type = Consts::OBJECT_TYPE_SESSION)
    {
        $this->userId = $userId;
        $this->data = (object) [
            'type' => $type
        ];

        $chatService = new ChatService();
        if ($type === Consts::OBJECT_TYPE_SESSION) {
            $this->data->record = $chatService->getUserSessionDetail($objectId, $this->userId);
        } else {
            $this->data->record = $chatService->getUserBountyDetail($objectId, $this->userId);
        }
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
