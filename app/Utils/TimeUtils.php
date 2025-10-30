<?php

namespace App\Utils;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class TimeUtils {

    const MINUTES_OF_DAY = 24 * 60; // 24:00 --> minutes

    public static function clientToUtc($strDate, $timeoffset, $format = 'Y-m-d H:i')
    {
        $hours = $timeoffset / 60 * -1;
        $tz = CarbonTimeZone::create($hours);

        return Carbon::createFromFormat($format, $strDate, $tz);
    }

    public static function time2Minutes($stringTime)
    {
        list($hours, $minutes) = explode(':', $stringTime);

        return $hours * 60 + $minutes;
    }

    public static function convertTimeRangesUtcToClient($timeRanges, $timeoffset)
    {
        $newTimeRanges = [];

        foreach ($timeRanges as $key => $value) {
            $converted = static::splitTimeRangeIfNeed(
                static::utcToClient($value, $timeoffset)
            );

            $isMulArray = !array_key_exists('from', $converted);
            $newTimeRanges = array_merge($newTimeRanges, $isMulArray ? $converted : [$converted]);
        }

        $result = [];
        collect($newTimeRanges)->sortBy('from')
            ->groupBy('weekday')
            ->each(function ($value) use (&$result) {
                $result = array_merge(
                    $result,
                    static::mergeTimeRanges($value->toArray())
                );
            });

        return collect($result)->transform(function ($value) {
            $value = (array) $value;
            $value['all'] = ($value['from'] === 0 && $value['to'] >= static::MINUTES_OF_DAY) || ($value['to'] === $value['from']);
            return $value;
        });
    }

    public static function utcToClient($timeRange, $timeoffset)
    {
        Carbon::setWeekStartsAt(Carbon::SUNDAY);

        $timezone = $timeoffset / 60 * -1;
        $startOfWeek = Carbon::now()->startOfWeek()->setTimezone($timezone);

        $startTime = $startOfWeek->copy()->addMinutes($timeRange->from);
        $endTime = $startOfWeek->copy()->addMinutes($timeRange->to);
        $newDay = static::time2Minutes($endTime->format('H:i')) === 0 && $endTime->weekday() !== $startTime->weekday();

        return [
            'from' => static::time2Minutes($startTime->format('H:i')),
            'to' => $newDay ? static::MINUTES_OF_DAY : static::time2Minutes($endTime->format('H:i')),
            'weekday' => $startTime->weekday()
        ];
    }

    public static function timeRangeToUtc($from, $to, $weekday, $timeoffset)
    {
        $utcFrom = static::calculateStartTimeByWeekday($from, $weekday, $timeoffset);
        $utcTo = static::calculateEndTimeByWeekday($to, $weekday, $timeoffset);

        $totalMinutesOfWeek = static::toMinutes(7 * 24);
        if ($utcTo < 0) {
            return [
                'from' => $utcFrom,
                'to' => $totalMinutesOfWeek + $utcTo
            ];
        }

        if ($utcFrom < $utcTo) {
            if ($utcFrom > $totalMinutesOfWeek) {
                return [
                    'from' => $utcFrom - $totalMinutesOfWeek,
                    'to' => $utcTo - $totalMinutesOfWeek
                ];
            }

            if ($utcTo > $totalMinutesOfWeek) {
                return [
                    [
                        'from' => $utcFrom,
                        'to' => $totalMinutesOfWeek
                    ],
                    [
                        'from' => 0,
                        'to' => $utcTo - $totalMinutesOfWeek
                    ]
                ];
            }

            return [
                'from' => $utcFrom,
                'to' => $utcTo
            ];
        }

        // start time is bigger than total minutes of week
        if ($utcFrom > $totalMinutesOfWeek) {
            return [
                'from' => 0,
                'to' => $utcTo
            ];
        }

        if ($utcTo === 0) {
            return [
                'from' => $utcFrom,
                'to' => $totalMinutesOfWeek
            ];
        }

        return [
            [
                'from' => $utcFrom,
                'to' => $totalMinutesOfWeek
            ],
            [
                'from' => 0,
                'to' => $utcTo
            ]
        ];
    }

    /*
     * @param $minutes
     * @param $weekday: it's client time.
     * @param $timeoffset: time offset of client like: -420(Vietnam), ...
     */
    private static function calculateStartTimeByWeekday($minutes, $weekday, $timeoffset)
    {
        $prevMinutes = static::toMinutes($weekday * 24);
        $result = $minutes + $prevMinutes + $timeoffset;

        $totalMinutesOfWeek = static::toMinutes(7 * 24);

        return $result < 0 ? $totalMinutesOfWeek + $result : $result;
    }

    /*
     * @param $minutes
     * @param $weekday: it's client time.
     * @param $timeoffset: time offset of client like: -420(Vietnam), ...
     */
    private static function calculateEndTimeByWeekday($minutes, $weekday, $timeoffset)
    {
        $prevMinutes = static::toMinutes($weekday * 24);
        return $minutes + $prevMinutes + $timeoffset;
    }

    private static function toMinutes($hours)
    {
        return $hours * 60;
    }

    public static function isOverlapTimeRange($data, $utcTime)
    {
        $utcTime = (object) $utcTime;

        return $data->contains(function ($item) use ($utcTime) {
            return static::isOverlap($item, $utcTime);
        });
    }

    private static function isOverlap ($e1, $e2)
    {
        return ($e1->from > $e2->from && $e1->from < $e2->to)
            || ($e2->from > $e1->from && $e2->from < $e1->to);
    }

    private static function mergeTimeRanges($timeRanges)
    {
        $size = count($timeRanges);
        if ($size <= 1) {
            return $timeRanges;
        }

        $result = [];

        for ($i = 0; $i < $size; $i++) {
            $current = (object) $timeRanges[$i];

            if (empty($timeRanges[$i + 1])) {
                $result[] = $current;
                continue;
            }

            $next = (object) $timeRanges[$i + 1];
            $isNumberContinuously = $current->to === $next->from;

            if ($isNumberContinuously) {
                $result[] = [
                    'from'      => $current->from,
                    'to'        => $next->to,
                    'weekday'   => $current->weekday,
                ];

                $i++;
                continue;
            }

            $result[] = $current;
        }

        return $result;
    }

    private static function splitTimeRangeIfNeed($timeRange)
    {
        if ($timeRange['from'] < $timeRange['to']) {
            return $timeRange;
        }

        $weekday = intval($timeRange['weekday']);

        return [
            [
                'from' => $timeRange['from'],
                'to' => static::MINUTES_OF_DAY,
                'weekday' => $weekday
            ],
            [
                'from' => 0,
                'to' => $timeRange['to'],
                'weekday' => $weekday === Carbon::SATURDAY ? 0 : ($weekday + 1)
            ]
        ];
    }
}
