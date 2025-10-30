<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Utils\RankingUtils;
use App\Models\Tasking;

class CollectTaskingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $type;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($userId, $type)
    {
        $this->userId   = $userId;
        $this->type     = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // switch ($this->type) {
        //     case Tasking::FOLLOW_USER:
        //     case Tasking::CREATE_SESSION:
        //         RankingUtils::collectUserTaskingByCode($this->userId, $this->type);
        //         break;
        //     case Tasking::UPLOAD_VIDEO_INTRO:
        //     case Tasking::UPLOAD_VIDEO_DAILY:
        //         $isCollected = RankingUtils::collectUserTaskingByCode($this->userId, Tasking::UPLOAD_VIDEO_INTRO);
        //         if (!$isCollected) {
        //             RankingUtils::collectUserTaskingByCode($this->userId, Tasking::UPLOAD_VIDEO_DAILY);
        //         }
        //         break;
        //     case Tasking::PLAY_FREE_SESSION:
        //     case Tasking::COMPLETE_SESSION:
        //         $isCollected = RankingUtils::collectUserTaskingByCode($this->userId, Tasking::PLAY_FREE_SESSION);
        //         if (!$isCollected) {
        //             RankingUtils::collectUserTaskingByCode($this->userId, Tasking::COMPLETE_SESSION);
        //         }
        //         break;
        //     default:
        //         break;
        // }
    }

}
