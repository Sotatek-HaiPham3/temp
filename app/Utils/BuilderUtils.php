<?php

namespace App\Utils;

use App\Consts;

class BuilderUtils
{
    public static function queryMultipleRanges($query, $property, $conditions = [])
    {
        if (!count($conditions)) {
            return;
        }

        $parseRange = function ($value) {
            return explode(Consts::CHAR_UNDERSCORE, $value);
        };

        $queryRange = function ($query, $property, $value) use ($parseRange) {
            if (!$value) {
                return;
            }

            list($start, $end) = $parseRange($value);
            return $query->when(!empty($start), function ($query2) use ($property, $start) {
                    $query2->where($property, '>=', $start);
                })
                ->when(!empty($end), function ($query2) use ($property, $end) {
                    $query2->where($property, '<=', $end);
                });
        };

        $last = array_pop($conditions);
        $query->when(!empty($last), function ($query2) use ($queryRange, $property, $last) {
            $query2->where(function ($query3) use ($queryRange, $property, $last) {
                return $queryRange($query3, $property, $last);
            });
        });

        foreach ($conditions as $value) {
            $query->orWhere(function ($query2) use ($queryRange, $property, $value) {
                return $queryRange($query2, $property, $value);
            });
        }

        return $query;
    }

    public static function multipleLike($query, $property, $data = [])
    {
        $last = array_pop($data);
        $query->when(!empty($last), function ($query2) use ($property, $last) {
            $query2->where($property, 'LIKE', "%{$last}%");
        });

        foreach ($data as $value) {
            $query->orWhere($property, 'LIKE', "%{$value}%");
        }

        return $query;
    }
}
