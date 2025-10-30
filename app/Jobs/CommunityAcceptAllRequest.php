<?php

namespace App\Jobs;

use App\Consts;
use App\Http\Services\CommunityService;
use App\Models\CommunityRequest;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CommunityAcceptAllRequest implements ShouldQueue
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
        $allRequest = CommunityRequest::where('community_id', $this->communityId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->get();

        if ($allRequest->count()) {
            foreach ($allRequest as $item) {
                $this->communityService->handleJobAcceptRequest($this->communityId, $item->user_id);
            }
        }
    }

    private function log(...$params)
    {
        logger('==========CommunityAcceptAllRequest: ', [$params]);
    }
}
