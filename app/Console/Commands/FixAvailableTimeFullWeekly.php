<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Utils;
use App\Http\Services\MasterdataService;
use App\Models\GamelancerAvailableTime;
use Carbon\Carbon;
use App\Utils\TimeUtils;
use DB;

class FixAvailableTimeFullWeekly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'available_time:full_weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert from URL server to URL CloudFront';

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
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        DB::beginTransaction();

        try {

            $userIds = GamelancerAvailableTime::whereNotNull('weekday')->get()->pluck('user_id')->unique();


            // logger()->info('adasdasdasdasda', [$data]);
            foreach ($userIds as $key => $user_id) {

                logger()->info('=======aaaaaa', [$user_id]);

                for ($i = 0; $i < 7; $i++) {
                    $time = [
                        'weekday' => $i,
                        'from' => 0,
                        'to' => 1440,
                        'user_id' => $user_id
                    ];
                    $this->addNewTime($time, 0);
                }
            }

            GamelancerAvailableTime::whereNotNull('weekday')->whereIn('user_id', $userIds)->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }

    private function addNewTime($value, $timeoffset)
    {
        if (($value['to'] + 1) % 60 === 0) {
            $value['to'] = 1440;
        }


        $result = TimeUtils::timeRangeToUtc($value['from'], $value['to'], $value['weekday'], -$timeoffset * 60);
        $result = array_key_exists('from', $result) ? [$result] : $result;

        $timeRanges = collect($result)->map(function ($item) use ($value) {
                $item['user_id'] = $value['user_id'];
                $item['created_at'] = now();
                $item['updated_at'] = now();

                return $item;
            })
            ->toArray();

        unset($timeRanges['weekday']);

        // if ($value['user_id'] === 74) {
        //     logger()->info('=================================addNewTime', [
        //         '$value' => $value,
        //         'timeoffset' => -$timeoffset * 60,
        //         '$timeRanges' => $timeRanges
        //     ]);
        // }

        GamelancerAvailableTime::insert($timeRanges);
    }
}
