<?php

namespace App\Jobs;

use App\Consts;
use App\Http\Services\VoiceService;
use App\Models\VoiceChatRoom;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CloseAllRoomCommunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $communityId;
    private $voiceService;

    /**
     * Create a new job instance.
     *
     * @param $communityId
     */
    public function __construct($communityId)
    {
        $this->communityId = $communityId;
        $this->voiceService = new VoiceService();
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
            return;
        } catch (Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    public function process()
    {
        $voiceChatRoom = VoiceChatRoom::where('community_id', $this->communityId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->get();
        if ($voiceChatRoom->count()) {
            foreach ($voiceChatRoom as $item) {
                $this->voiceService->closeRoomCommunity($item->id);
            }
        }
    }

    private function log(...$params)
    {
        logger('==========CloseAllRoom: ', [$params]);
    }
}
