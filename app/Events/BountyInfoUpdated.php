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
use App\Http\Services\BountyService;
use App\Models\Bounty;

class BountyInfoUpdated extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($bountyId)
    {
        $bounty = Bounty::find($bountyId);
        $bountyService = new BountyService();
        $this->data = $bountyService->getBountyDetail($bounty->slug);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('App.BountyInfoUpdated');
    }
}
