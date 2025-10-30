<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\RankingUtils;
use App\Utils\ChatUtils;
use App\Events\UserUpdated;
use App\Events\UserLevelUp;
use DB;

class CalculateUserRanking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userRanking;

    /**
     * Create a new job instance.
     *
     * @param $user
     * @param $currencies
     */
    public function __construct($userRanking)
    {
        $this->userRanking = $userRanking;
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
        } catch (\Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    private function process()
    {
        $oldRankId = $this->userRanking->ranking_id;

        $ranking = RankingUtils::getRankingByExp($this->userRanking->total_exp);

        $this->userRanking->ranking_id = $ranking->id;
        $this->userRanking->save();

        // save new info of user to cache.
        $userData = ChatUtils::getUserDataToCache($this->userRanking->user_id);
        ChatUtils::updateChannelMembers($userData);

        event(new UserUpdated($this->userRanking->user_id));

        if ($oldRankId < $ranking->id) {
            event(new UserLevelUp($this->userRanking->user_id, $ranking));
            RankingUtils::fireSystemNotificationLevelUp($this->userRanking->user_id, $ranking);
        }
    }

    private function log(...$params)
    {
        logger('====CalculateUserRanking: ', [$params]);
    }
}
