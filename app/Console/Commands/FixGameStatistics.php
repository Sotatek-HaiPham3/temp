<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Consts;
use App\Utils\GameStatisticUtils;
use App\Models\GameProfile;
use App\Models\Session;
use App\Models\GameStatistic;
use DB;
use Exception;
use Carbon\Carbon;

class FixGameStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:fix-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate game statistics for old game profile';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            GameProfile::where('is_active', Consts::TRUE)->get()->each(function ($gameProfile) use (&$now) {
                $gameStatistic = GameStatisticUtils::createNewGameStatistic($gameProfile);

                if ($this->canNotUpdateForSessioncompleted($gameStatistic, $now)) {
                    return;
                }

                Session::where('game_profile_id', $gameProfile->id)
                    ->whereIn('status', [
                        Consts::SESSION_STATUS_COMPLETED,
                        Consts::SESSION_STATUS_STOPPED
                    ])
                    ->get()
                    ->each(function ($session) use ($gameStatistic) {
                        GameStatisticUtils::updateForSessioncompleted($session, $gameStatistic);
                    });
            });
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function canNotUpdateForSessioncompleted($gameStatistic, $now)
    {
        if (empty($gameStatistic)) {
            return false;
        }

        if ($now->gt($gameStatistic->executed_date)) {
            return true;
        }

        return false;
    }
}
