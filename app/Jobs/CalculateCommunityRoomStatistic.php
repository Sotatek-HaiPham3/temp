<?php

namespace App\Jobs;

use App\Consts;
use App\Events\CommunityInfoUpdated;
use App\Http\Services\CommunityService;
use App\Models\Community;
use App\Models\VoiceChatRoom;
use App\Models\VoiceChatRoomUser;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateCommunityRoomStatistic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $communityId;
    private $communityService;

    /**
     * Create a new job instance.
     *
     * @param $communityId
     */
    public function __construct($communityId)
    {
        $this->communityId = $communityId;
        $this->communityService = new CommunityService();
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
            $community = Community::where('id', $this->communityId)->first();
            if ($community->inactive_at) {
                $permanentlyDeleteAt = Carbon::createFromFormat('Y-m-d H:i:s', $community->inactive_at)->addDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD)->format('Y-m-d H:i:s');
                $community->permanently_delete_at = $permanentlyDeleteAt;
            }

            $baseQuery = VoiceChatRoom::where('status', Consts::VOICE_ROOM_STATUS_CALLING)
                ->where('is_private', Consts::FALSE)
                ->where('community_id', $this->communityId);

            $totalSize = $baseQuery->select(DB::raw('sum(size) AS `size`'))->first()->size;
            $totalRooms = $this->getTotalRooms($this->communityId);
            $community->total_rooms = count($totalRooms);
            $community->total_rooms_size = (int) $totalSize ?: 0;
            $community->total_rooms_user = $this->getTotalRoomUsers($totalRooms);
            $community->save();
            event(new CommunityInfoUpdated($community));

            DB::commit();
            return;
        } catch (Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    private function getTotalRooms($communityId)
    {
        return VoiceChatRoom::where('community_id', $communityId)
            ->where('status', '<>', Consts::VOICE_ROOM_STATUS_ENDED)
            ->where('is_private', Consts::FALSE)
            ->pluck('id');
    }

    private function getTotalRoomUsers($arrayVoiceId)
    {
        return VoiceChatRoomUser::whereIn('room_id', $arrayVoiceId)
            ->whereNull('ended_time')
            ->count();
    }

    private function log(...$params)
    {
        logger('==========CalculateCommunityStatistic: ', [$params]);
    }
}
