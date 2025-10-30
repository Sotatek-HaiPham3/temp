<?php

namespace App\Utils;

use App\Http\Services\MasterdataService;
use App\Http\Services\UserService;
use App\Models\Tasking;
use App\Models\TaskingReward;
use App\Models\UserRanking;
use App\Models\CollectingTasking;
use App\Models\CollectingTaskingReward;
use App\Models\ExperiencePoint;
use App\Models\DailyCheckin;
use App\Jobs\CalculateUserRanking;
use App\Consts;
use App\Exceptions\Reports\InvalidActionException;
use App\Mails\IntroTaskCompletionMail;
use Mail;
use DB;
use App\Jobs\SendSystemNotification;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class RankingUtils {

    const DAILY_TASK_TIMEZONE_DEFAULT   = 0; // UTC
    const NUMBER_DAY_MAXIMUM_CHECKIN    = 14; // UTC

    public static function getRankingByExp($exp = 0)
    {
        $exp = BigNumber::new($exp)->isNegative() ? 0 : $exp;
        return MasterdataService::getOneTable('rankings')
            ->sortBy('exp')
            ->filter(function ($item) use ($exp) {
                return BigNumber::new($item->exp)->comp($exp) < 1; // less or equal
            })
            ->last();
    }

    private static function getRankingDefault ()
    {
        return MasterdataService::getOneTable('rankings')
            ->sortBy('exp')
            ->first();
    }

    public static function getTaskingCollected($userId)
    {
        $introTaskingCollected = static::getTaskingCollectedBuilder([
            'user_id' => $userId,
            'type'    => Consts::TASKING_TYPE_INTRO
        ]);

        $dailyTaskingCollected = static::getTaskingCollectedBuilder([
            'user_id' => $userId,
            'type'    => Consts::TASKING_TYPE_DAILY
        ]);

        return $introTaskingCollected->concat($dailyTaskingCollected)
            ->groupBy('tasking_id');
    }

    public static function getDailyTaskTimezone()
    {
        $result = MasterdataService::getOneTable('settings')
            ->where('key', Consts::DAILY_TASK_TIMEZONE_KEY)
            ->first();

        $timeoffset = $result ? $result->value : static::DAILY_TASK_TIMEZONE_DEFAULT;
        $hours = $timeoffset / 60 * -1;
        return CarbonTimeZone::create($hours);
    }

    public static function standardDailyTime($date)
    {
        $timezone = static::getDailyTaskTimezone();
        return Carbon::parse($date)->setTimezone($timezone);
    }

    private static function getStartAndEndUtcTime()
    {
        $now = Carbon::now();
        $now = static::standardDailyTime($now);

        $utcStartDay = $now->copy()->startOfDay()->setTimezone(0);
        $utcEndDay = $now->copy()->endOfDay()->setTimezone(0);

        return [$utcStartDay, $utcEndDay];
    }

    public static function getNumberDayMaximumCheckin($userId, $checkinMilestone)
    {
        return DailyCheckin::where('user_id', $userId)
            ->where('milestone', $checkinMilestone)
            ->get()
            ->count();
    }

    public static function awardForUser($userId, $currency, $value)
    {
        switch ($currency) {
            case Consts::CURRENCY_EXP:
                $ranking = UserRanking::firstOrCreate([
                    'user_id' => $userId
                ]);
                $ranking->ranking_id = $ranking->ranking_id ?: static::getRankingDefault()->id;
                $ranking->total_exp = BigNumber::new($ranking->total_exp)->add($value)->toString();
                $ranking->save();

                CalculateUserRanking::dispatch($ranking)->onQueue(Consts::RANKING_QUEUE);
                break;
            case Consts::CURRENCY_COIN:
            case Consts::CURRENCY_BAR:
                $userService = new UserService;
                $userService->addMoreBalance($userId, $value, Consts::CURRENCY_COIN);
                break;
            default:
                logger()->info("===============AwardForUser:: {$currency} currency doesn't support.");
                break;
        }
    }

    public static function collectUserTaskingByCode($userId, $code)
    {
        $tasking = Tasking::where('code', $code)->first();

        if (!$tasking) {
            return false;
        }

        $userService = new UserService;
        $isCollected = $userService->collectUserTasking($tasking->id, $userId);

        if (!$isCollected) {
            return false;
        }

        static::awardForUser($userId, Consts::CURRENCY_EXP, $tasking->exp);

        $value = $tasking->bonus_value;
        $currency = $tasking->bonus_currency;

        if ($currency && $value) {
            RankingUtils::awardForUser($userId, $currency, $value);
        }

        return true;
    }

    public static function fireNotification($userId, $taskingId)
    {
        $tasking = Tasking::find($taskingId);
        $user = DB::table('users')->where('id', $userId)->first();

        switch ($tasking->type) {
            case Consts::TASKING_TYPE_INTRO:
                $totalTask = static::getTaskings($tasking->type)->count();
                $quantityCollected = static::sumQuantityTaskingCollected([
                    'user_id' => $userId,
                    'type'    => $tasking->type,
                ]);

                if ($quantityCollected >= $totalTask) {
                    $highestReward = TaskingReward::orderBy('level')->get()->last();
                    $mailable = new IntroTaskCompletionMail($user, $highestReward->quantity);
                    Mail::queue($mailable);
                }
                static::fireSystemNotification($user, $tasking);
                break;
            case Consts::TASKING_TYPE_DAILY:
                $collecting = static::getTaskingCollected($userId);
                $taskingSummary = (object) $tasking->getUserTasks($collecting);

                if ($taskingSummary->total_collected >= $taskingSummary->max_quantity) {
                    static::fireSystemNotification($user, $taskingSummary);
                }
                break;
            default:
                break;
        }
    }

    private static function fireSystemNotification ($user, $tasking)
    {
        $maxQuantity = !property_exists($tasking, 'max_quantity') || empty($tasking->max_quantity) ? 1 : $tasking->max_quantity;
        $params = [
            'user_id'   => $user->id,
            'type'      => Consts::NOTIFY_TYPE_TASKING_COMPLETED,
            'message'   => Consts::MESSAGE_NOTIFY_TASKING_COMPLETED,
            'props'     => [
                'type'      => $tasking->type,
                'currency'  => Consts::CURRENCY_EXP,
                'quantity'  => BigNumber::new($tasking->exp)->mul($maxQuantity)->toString()
            ],
            'data'      => [
                'user'      => (object) ['id' => $user->id],
                'mailable'  => null
            ]
        ];
        SendSystemNotification::dispatch(Consts::NOTIFY_TYPE_TASKING, $params)->onQueue(Consts::QUEUE_NOTIFICATION);
    }

    public static function fireSystemNotificationLevelUp ($userId, $ranking)
    {
        $params = [
            'user_id'   => $userId,
            'type'      => Consts::NOTIFY_TYPE_TASKING_LEVEL_UP,
            'message'   => Consts::MESSAGE_NOTIFY_TASKING_LEVEL_UP,
            'props'     => [
                'rank_name' => $ranking->name
            ],
            'data'      => [
                'user'      => (object) ['id' => $userId],
                'mailable'  => null
            ]
        ];
        SendSystemNotification::dispatch(Consts::NOTIFY_TYPE_TASKING_LEVEL_UP, $params)->onQueue(Consts::QUEUE_NOTIFICATION);
    }

    public static function validateClaimingTasking($userId, $type, $levelReward)
    {
        $quantityCollected = static::sumQuantityTaskingCollected([
            'user_id'   => $userId,
            'type'      => $type,
            'level'     => $levelReward
        ]);

        $reward = TaskingReward::where('type', $type)
            ->where('level', $levelReward)
            ->first();

        if (!$reward || $reward->level > $quantityCollected) {
            static::throwInvalidActionException();
        }

        $query = CollectingTaskingReward::where('user_id', $userId)
            ->where('tasking_reward_id', $reward->id);

        switch ($type) {
            case Consts::TASKING_TYPE_DAILY:
                list($utcStartDay, $utcEndDay) = static::getStartAndEndUtcTime();
                $query->where('created_at', '>=', $utcStartDay)
                    ->where('created_at', '<=', $utcEndDay);
                break;
            case Consts::TASKING_TYPE_DAILY_CHECKIN:
                $userRanking = UserRanking::firstOrCreate(['user_id' => $userId]);
                $maxDayOfMilestone = static::getNumberDayMaximumCheckin($userId, $userRanking->checkin_milestone);
                $endDayOfMilestone = Carbon::parse($userRanking->checkin_milestone)->addDays($maxDayOfMilestone)->format('Y-m-d H:i:s');

                $query->where('created_at', '>=', $userRanking->checkin_milestone)
                    ->where('created_at', '<=', $endDayOfMilestone);
                break;
        }

        if ($query->exists()) {
            static::throwInvalidActionException();
        }

        return $reward;
    }

    public static function overThresholdTasking($userId, $taskingId)
    {
        $tasking = Tasking::find($taskingId);

        $totalCollected = static::sumQuantityTaskingCollected([
            'user_id'       => $userId,
            'type'          => $tasking->type,
            'tasking_id'    => $tasking->id
        ]);

        $expAwarding = BigNumber::new($tasking->exp)->mul($totalCollected);

        // over threshold
        if (~$expAwarding->comp($tasking->threshold_exp_in_day)) {
            // static::throwInvalidActionException();
            return true;
        }

        return false;
    }

    public static function getTaskingRewardCollected($userId)
    {
        $introTaskingCollected = static::getTaskingRewardBuilder([
            'user_id' => $userId,
            'type'    => Consts::TASKING_TYPE_INTRO
        ]);

        $dailyTaskingCollected = static::getTaskingRewardBuilder([
            'user_id' => $userId,
            'type'    => Consts::TASKING_TYPE_DAILY
        ]);

        $userRanking = UserRanking::where('user_id', $userId)->first();
        $dailyCheckinCollected = static::getTaskingRewardBuilder([
            'user_id'   => $userId,
            'type'      => Consts::TASKING_TYPE_DAILY_CHECKIN,
            'milestone' => $userRanking ? $userRanking->checkin_milestone : null
        ]);

        return $introTaskingCollected->concat($dailyTaskingCollected)
            ->concat($dailyCheckinCollected)
            ->groupBy('tasking_reward_id');
    }

    public static function initializeNewMilestoneDailyCheckin(UserRanking $userRanking)
    {
        if ($userRanking->checkin_milestone) {
            $currentMilestoneChecked = DailyCheckin::where('user_id', $userRanking->user_id)
                ->where('milestone', $userRanking->checkin_milestone)
                ->where('day', 1)
                ->whereNotNull('checked_at')
                ->exists();

            if (!$currentMilestoneChecked) {
                DailyCheckin::where('user_id', $userRanking->user_id)
                    ->where('milestone', $userRanking->checkin_milestone)
                    ->delete();
            }
        }

        $utcNow = now();
        $userRanking->checkin_milestone = static::standardDailyTime($utcNow)
            ->startOfDay()
            ->setTimezone(0);

        $userRanking->save();

        return ExperiencePoint::all()
            ->map(function ($record) use ($userRanking) {
                return DailyCheckin::create([
                    'user_id'       => $userRanking->user_id,
                    'milestone'     => $userRanking->checkin_milestone,
                    'day'           => $record->day,
                    'exp'           => $record->exp
                ]);
            });
    }

    public static function isContinuousDailyCheckin($data, $checkin)
    {
        $maxSize = count($data);
        $standardValue = $maxSize * ($maxSize + 1) / 2;

        $total = collect($data)
            ->map(function ($record) use ($checkin) {
                if ($record->id === $checkin->id) {
                    $record->checked_at = now();
                }
                return $record;
            })
            ->filter(function ($record) {
                return !empty($record->checked_at);
            })
            ->sum('day');

        return $standardValue === intval($total);
    }

    public static function getTodayCheckinSetting($userRanking)
    {
        $startMilestone = Carbon::parse($userRanking->checkin_milestone);
        $checkinDay = $startMilestone->diffInDays(now()) + 1;
        return DailyCheckin::where('user_id', $userRanking->user_id)
            ->where('milestone', $userRanking->checkin_milestone)
            ->where('day', $checkinDay)
            ->first();
    }

    private static function getTaskingRewardBuilder($params = [])
    {
        $type = array_get($params, 'type', null);
        $userId= array_get($params, 'user_id');
        $checkinMilestone = array_get($params, 'milestone', null);

        return CollectingTaskingReward::join('tasking_rewards', 'tasking_rewards.id', 'collecting_tasking_rewards.tasking_reward_id')
            ->where('collecting_tasking_rewards.user_id', $userId)
            ->when(!empty($type), function ($query) use ($type, $checkinMilestone, $userId) {
                $query = $query->where('tasking_rewards.type', $type);

                switch ($type) {
                    case Consts::TASKING_TYPE_INTRO:
                        // Do something
                        break;
                    case Consts::TASKING_TYPE_DAILY_CHECKIN:
                        // $checkinMilestone is daily time that is converted to UTC time.
                        if ($checkinMilestone) {
                            $maxDayOfMilestone = static::getNumberDayMaximumCheckin($userId, $checkinMilestone);
                            $endDayOfMilestone = Carbon::parse($checkinMilestone)->addDays($maxDayOfMilestone)->format('Y-m-d H:i:s');

                            $query->where('collecting_tasking_rewards.created_at', '>=', $checkinMilestone)
                                ->where('collecting_tasking_rewards.created_at', '<=', $endDayOfMilestone);
                        }
                        break;
                    case Consts::TASKING_TYPE_DAILY:
                        list($utcStartDay, $utcEndDay) = static::getStartAndEndUtcTime();
                        $query->where('collecting_tasking_rewards.created_at', '>=', $utcStartDay)
                            ->where('collecting_tasking_rewards.created_at', '<=', $utcEndDay);
                        break;
                }
            })
            ->get();
    }

    private static function getTaskingCollectedBuilder ($params = [])
    {
        $userId     = array_get($params, 'user_id');
        $type       = array_get($params, 'type', null);
        $taskingId  = array_get($params, 'tasking_id', null);

        return CollectingTasking::join('taskings', 'taskings.id', 'collecting_taskings.tasking_id')
            ->where('collecting_taskings.user_id', $userId)
            ->when(!empty($type), function ($query) use ($type) {
                $query = $query->where('taskings.type', $type);

                switch ($type) {
                    case Consts::TASKING_TYPE_INTRO:
                        break;
                    case Consts::TASKING_TYPE_DAILY:
                        list($utcStartDay, $utcEndDay) = static::getStartAndEndUtcTime();
                        $query->where('collecting_taskings.created_at', '>=', $utcStartDay)
                            ->where('collecting_taskings.created_at', '<=', $utcEndDay);
                        break;
                }
            })
            ->when(!empty($taskingId), function ($query) use ($taskingId) {
                $query->where('collecting_taskings.tasking_id', $taskingId);
            })
            ->get();
    }

    private static function getCheckinDayRewardCollected($params = [])
    {
        $userId = array_get($params, 'user_id');
        $level = array_get($params, 'level', 0);
        $type = array_get($params, 'type');
        $userRanking = UserRanking::firstOrCreate(['user_id' => $userId]);

        if (!$userRanking->checkin_milestone) {
            return 0;
        }

        return static::isAlreadyCheckinContinuous($userRanking, $level) ? $level : 0;
    }

    public static function isAlreadyCheckinContinuous($userRanking, $numOfDay)
    {
        $standardValue = $numOfDay * ($numOfDay + 1) / 2;

        $totalDay = DailyCheckin::where('user_id', $userRanking->user_id)
            ->where('milestone', $userRanking->checkin_milestone)
            ->whereNotNull('checked_at')
            ->where('day', '<=', $numOfDay)
            ->sum('day');

        return intval($totalDay) === $standardValue;
    }

    private static function sumQuantityTaskingCollected($params = [])
    {
        $type = array_get($params, 'type');
        switch ($type) {
            case Consts::TASKING_TYPE_DAILY_CHECKIN:
                return static::getCheckinDayRewardCollected($params);
                break;
            default:
                return static::getTaskingCollectedBuilder($params)->sum('quantity');
                break;
        }
    }

    private static function getTaskings($type = null)
    {
        return Tasking::when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->get();
    }

    private static function throwInvalidActionException()
    {
        throw new InvalidActionException();
    }
}
