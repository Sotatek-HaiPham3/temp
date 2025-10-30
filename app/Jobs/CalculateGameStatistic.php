<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Consts;
use App\Utils\GameStatisticUtils;
use Exception;
use DB;

class CalculateGameStatistic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $gameId;
    private $data;
    private $type;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($gameId, $data, $type)
    {
        $this->gameId = $gameId;
        $this->data = $data;
        $this->type = $type;
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
            switch ($this->type) {
                case Consts::GAME_STATISTIC_CREATE_GAME_PROFILE:
                    GameStatisticUtils::createNewGameStatistic($this->data);
                    break;
                case Consts::GAME_STATISTIC_SESSION_COMPLETED:
                case Consts::GAME_STATISTIC_SESSION_STOPPED:
                    GameStatisticUtils::updateForSessioncompleted($this->data);
                    break;
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();

            logger()->error('==========CalculateGameStatistic==========: ', [$e]);
        }
    }
}
