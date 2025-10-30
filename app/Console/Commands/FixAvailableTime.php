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

class FixAvailableTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'available_time:fix';

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

            $data = GamelancerAvailableTime::all();

            list($usersConverting, $usersInvalid) = $this->processRawData($data);

            $usersTimezone = [];
            collect($usersConverting)->each(function ($item) use (&$usersTimezone) {
                $usersTimezone[$item->user_id] = $item->timezone;
            });

            $result = $this->utc2Client($data, $usersTimezone);
            logger()->info($result);

            // logger()->info('====result = ', [$result->toArray()]);

            // logger()->info('====Remaining size usersConverting = ', [$usersTimezone]);

            $userIds = $usersConverting->pluck('user_id')->toArray();
            logger()->info('====Remaining size userIds = ', [count($userIds)]);
            logger()->info('====Remaining size usersInvalid = ', [$usersInvalid]);

            GamelancerAvailableTime::whereNotNull('weekday')
                ->whereIn('user_id', $userIds)
                ->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            logger()->error($ex);
        }
    }

    private function processRawData($data)
    {
        $usersConverting = $data->filter(function ($item) {
                return $item->all || ($item->from - $item->to === 1);
            })
            ->unique('user_id')
            ->keyBy('user_id')
            ->map(function ($item) {
                unset($item->created_at);
                unset($item->updated_at);

                $item->timezone = $this->getTimezone($item);

                return $item;
            });

        $userIds = $usersConverting->pluck('user_id')->toArray();

        $remainingUsers = $data->filter(function ($item) use ($userIds) {
                return !in_array($item->user_id, $userIds);
            })
            ->unique('user_id')
            ->pluck('user_id')
            ->toArray();

        return [$usersConverting, $remainingUsers];
    }

    private function getTimezone($item)
    {
        $hour = $item->from / 60;
        return $hour <= 12 ? -$hour : (24 - $hour);
    }

    private function utc2Client($data, $usersTimezone)
    {
        $result = [];
        $data->each(function ($item) use ($usersTimezone, &$result) {
            if (empty($usersTimezone[$item->user_id])) {
                return;
            }

            $timezone = $usersTimezone[$item->user_id];

            $resFrom = $this->clientTime($timezone, $item->from, $item->weekday);
            $resTo = $this->clientTime($timezone, $item->to, $item->weekday);

            // logger()->info('------------------', [
            //     'from-i' => $item->from,
            //     'to-i' => $item->to,
            //     'weekday-i' => $item->weekday,
            //     'from' => $resFrom,
            //     'timezone' => $usersTimezone[$item->user_id]
            // ]);

            $time = [
                'user_id'   => $item->user_id,
                'weekday'   => $resFrom->weekday,
                'from'      => $resFrom->time,
                'to'        => $resTo->time,
                'timeoffset' => $usersTimezone[$item->user_id],
            ];

            // if (!empty($result)) {
            //     return;
            // }

            $result[] = $time;

            $this->addNewTime($time, $usersTimezone[$item->user_id]);
        });

        return $result;
    }

    public function time2Minutes($stringTime)
    {
        list($hours, $minutes) = explode(':', $stringTime);

        return $hours * 60 + $minutes;
    }

    public function clientTime($timeoffset, $minutes, $weekday)
    {
        // logger()->info('-------', [
        //     'weekday' => $weekday,
        //     'minutes' => $minutes,
        //     'timeoffset' => $timeoffset,
        // ]);

        $hours = $timeoffset;
        $time = Carbon::now()
            ->startOfDay()
            ->weekday($weekday)
            ->addMinutes($minutes)
            ->setTimezone($hours);

        return (object) [
            'time' => $this->time2Minutes($time->format('H:i')),
            'weekday' => $time->weekday()
        ];
    }

    private function test()
    {
        $users = [];
        $ingore = [];

        $data = GamelancerAvailableTime::all();

        $filtered1 = $data
            ->map(function ($model) {
                $model->all_day = $model->from > $model->to;
                return $model;
            })
            ->filter(function ($model) {
                return $model->all_day;
            });

        $userIds = $filtered1->unique('user_id')->pluck('user_id')->all();

        $filtered2 = $data
            ->map(function ($model) {
                $model->all_day = abs($model->from - $model->to) == 1;
                return $model;
            })
            ->filter(function ($model) use ($userIds) {
                return !in_array($model->user_id, $userIds);
            })
            ->unique('user_id');

        $size1 = count($userIds);
        $size2 = $filtered2->count();
        echo "Filtered 1: {$size1} \nFiltered 2: {$size2} \n";

        logger()->info('user = ', [ $filtered2->pluck('user_id')->all() ]);
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
