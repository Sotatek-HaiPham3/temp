<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utils\TimeUtils;
use App\Exceptions\Reports\InvalidTimeRangeException;
use Auth;

class GamelancerAvailableTime extends Model
{
    protected $fillable = [
        'user_id',
        'weekday',
        'from', // minutes
        'to', // minutes
        'all'
    ];

    const MINUTES_OF_DAY = 24 * 60;

    public static function addNewTime($data, $timeoffset)
    {
        $timeRanges = [];

        foreach ($data as $key => $value) {
            $allDay = !empty($value['all']);

            if ($allDay) {
                $value['from'] = 0;
                $value['to'] = TimeUtils::MINUTES_OF_DAY;
            }

            $result = TimeUtils::timeRangeToUtc($value['from'], $value['to'], $value['weekday'], $timeoffset);
            $result = array_key_exists('from', $result) ? [$result] : $result;

            if ($allDay) {
                static::deleteAnotherTimeInDay($result, $timeoffset);
            }

            $timeRanges = array_merge(
                $timeRanges,
                collect($result)->map(function ($value) {
                    $value['user_id'] = Auth::id();
                    $value['created_at'] = now();
                    $value['updated_at'] = now();

                    return $value;
                })
                ->toArray()
            );
        }

        static::validateOverlapTimeRanges($timeRanges);

        GamelancerAvailableTime::insert($timeRanges);

        return $timeRanges;
    }

    public static function validateOverlapTimeRanges($timeRanges)
    {
        $availableTimes = Auth::user()->availableTimes()->get();

        $isOverlap = collect($timeRanges)->contains(function ($timeRange) use ($availableTimes) {
            return TimeUtils::isOverlapTimeRange($availableTimes, $timeRange);
        });

        if (!$isOverlap) {
            return true;
        }

        throw new InvalidTimeRangeException();
    }

    private static function deleteAnotherTimeInDay($timeRanges, $timeoffset)
    {
        $userId = Auth::id();
        foreach ($timeRanges as $key => $value) {
            // delete subset value
            GamelancerAvailableTime::where('user_id', $userId)
                ->where('from', '>=', $value['from'])
                ->where('to', '<=', $value['to'])
                ->delete();

            // update intersect value
            GamelancerAvailableTime::where('user_id', $userId)
                ->where('from', '>=', $value['from'])
                ->where('from', '<', $value['to'])
                ->update(['from' => $value['to']]);

            GamelancerAvailableTime::where('user_id', $userId)
                ->where('to', '>', $value['from'])
                ->where('to', '<=', $value['to'])
                ->update(['to' => $value['from']]);
        }
    }

    public static function deleteAvailableTime($params)
    {
        $timeoffset = $params['timeoffset'];
        $from = array_get($params, 'from', 0);
        $to = array_get($params, 'to', TimeUtils::MINUTES_OF_DAY);
        $result = TimeUtils::timeRangeToUtc($from, $to, $params['weekday'], $timeoffset);
        $result = array_key_exists('from', $result) ? [$result] : $result;
        return static::deleteAnotherTimeInDay($result, $timeoffset);
    }
}
