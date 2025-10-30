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
use App\Models\SessionSystemMessage;

class SessionSystemMessageUpdated extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($systemMessageId, $userId)
    {
        $systemMessage = SessionSystemMessage::find($systemMessageId);
        $this->userId = $userId;
        $this->data = cloneDeep((object) ['system_msg' => $systemMessage]);
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
