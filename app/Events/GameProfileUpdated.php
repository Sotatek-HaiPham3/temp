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
use App\Http\Services\GameProfileService;
use App\Models\GameProfile;

class GameProfileUpdated extends AppBroadcastEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($gameProfileId)
    {
        $this->data = [];
        $gameProfile = GameProfile::find($gameProfileId);

        if ($gameProfile) {
            $gameProfileService = new GameProfileService();
            $data = $gameProfileService->getGameProfileDetail([
                'username' => $gameProfile->user->username,
                'slug' => $gameProfile->game->slug
            ]);
            $this->data = cloneDeep($data);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('App.GameProfileUpdated');
    }
}
