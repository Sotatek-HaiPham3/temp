<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Reports\InvalidActionException;
use App\Models\VoiceChatRoom;
use App\Models\VoiceChatRoomUser;
use App\Events\VoiceChatRoomUpdated;
use App\Events\RoomInfoUpdated;
use App\Utils\VoiceGroupUtils;
use Exception;
use DB;

class CalculateRoomCurrentSize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $roomId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($roomId)
    {
        $this->roomId = $roomId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->process();
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    public function process()
    {
        $room = VoiceChatRoom::with(['host'])->where('id',$this->roomId)->first();
        if (!$room) {
            throw new InvalidActionException();
        }

        $currentSize = VoiceChatRoomUser::where('room_id', $this->roomId)
            ->whereNull('ended_time')
            ->count();
        $room->current_size = $currentSize;
        $room->save();

        $room->video = VoiceGroupUtils::getShareVideoByRoomId($this->roomId);

        if ($room->current_size) {
            event(new VoiceChatRoomUpdated($room));
        }

        return $room;
    }

    private function log(...$params)
    {
        logger('==========CalculateRoomCurrentSize: ', [$params]);
    }
}
