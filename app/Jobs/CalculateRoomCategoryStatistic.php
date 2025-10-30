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
use App\Models\RoomCategory;
use App\Events\VoiceCategoryUpdated;
use App\Consts;
use Exception;
use DB;

class CalculateRoomCategoryStatistic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $gameId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($gameId)
    {
        $this->gameId = $gameId;
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
        $roomCategory = RoomCategory::where('game_id', $this->gameId)->first();
        if (!$roomCategory) {
            throw new InvalidActionException();
        }

        $voiceChatRooms = VoiceChatRoom::where('game_id', $this->gameId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->whereNull('community_id')
            ->where('is_private', Consts::FALSE)
            ->get();

        $countUsers = $this->calculateTotalUser($voiceChatRooms);

        $roomCategory->total_room = $voiceChatRooms->count();
        $roomCategory->total_user = $countUsers['total_users'];
        $roomCategory->save();

        $custom = collect($countUsers);
        $roomCategory = $custom->merge($roomCategory);

        event(new VoiceCategoryUpdated($roomCategory));

        return $roomCategory;
    }

    private function calculateTotalUser($voiceChatRooms)
    {
        $totalAmaUsers = 0;
        $totalPlayUsers = 0;
        $totalHangoutUsers = 0;
        $totalCommunityUsers = 0;

        $voiceChatRooms->each(function ($item) use (&$totalAmaUsers, &$totalPlayUsers, &$totalHangoutUsers, &$totalCommunityUsers) {
            if ($item->type === Consts::ROOM_TYPE_AMA) {
                $totalAmaUsers += $item->current_size;
            }
            if ($item->type === Consts::ROOM_TYPE_PLAY) {
                $totalPlayUsers += $item->current_size;
            }
            if ($item->type === Consts::ROOM_TYPE_HANGOUT) {
                $totalHangoutUsers += $item->current_size;
            }
            if ($item->type === Consts::ROOM_TYPE_COMMUNITY) {
                $totalCommunityUsers += $item->current_size;
            }
        });

        return [
            'ama_users' => $totalAmaUsers,
            'play_users' => $totalPlayUsers,
            'hangout_users' => $totalHangoutUsers,
            'total_users' => $totalAmaUsers + $totalPlayUsers + $totalHangoutUsers + $totalCommunityUsers
        ];
    }

    private function log(...$params)
    {
        logger('==========CalculateRoomCategoryStatistic: ', [$params]);
    }
}
