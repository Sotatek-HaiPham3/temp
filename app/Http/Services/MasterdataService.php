<?php

namespace App\Http\Services;

use App\Consts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use App\Models\Reason;
use App\Models\Game;

class MasterdataService
{
    protected static $localData = null;

    public static function getDataVersion()
    {
        if (Cache::has('dataVersion')) {
            return Cache::get('dataVersion');
        }

        self::getAllData();
        return Cache::get('dataVersion');
    }

    public static function getAllData()
    {
        if (self::$localData != null) {
            return self::$localData;
        }

        if (Cache::has('masterdata') && Cache::has('dataVersion')) {
            if (self::$localData == null) {
                self::$localData = Cache::get('masterdata');
            }

            return self::$localData;
        }

        $data = [];

        foreach (Consts::MASTERDATA_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table);

                switch ($table) {
                    case 'reasons':
                        $data[$table] = static::getReasons();
                        break;

                    case 'games':
                        $data[$table] = static::getGames();
                        break;

                    case 'settings':
                        $data[$table] = static::getSettings();
                        break;

                    case 'languages':
                        $data[$table] = static::getLanguages();
                        break;

                    default:
                        $data[$table] = $query->when(Schema::hasColumn($table, 'is_active'), function ($subQuery) {
                                $subQuery->where('is_active', Consts::TRUE);
                            })
                            ->when(Schema::hasColumn($table, 'deleted_at'), function ($subQuery) {
                                $subQuery->whereNull('deleted_at');
                            })
                            ->when(Schema::hasColumn($table, 'id'), function ($subQuery) {
                                $subQuery->orderBy('id', 'asc');
                            })
                            ->get();
                        break;
                }
            }
        }

        Cache::forever('masterdata', $data);
        $dataVersion = sha1(json_encode($data));
        Cache::forever('dataVersion', $dataVersion);
        return $data;
    }

    public static function getOneTable($table)
    {
        $key = 'masterdata_' . $table;
        if (Cache::has($key)) {
            return collect(Cache::get($key));
        }

        $data = [];
        $allData = self::getAllData();
        if (!empty($allData[$table])) {
            $data = $allData[$table];
            Cache::forever($key, $data);
        }

        return collect($data);
    }

    public static function clearCacheOneTable($table)
    {
        static::$localData = null;
        Cache::forget("masterdata_$table");
        Cache::forget('dataVersion');
        Cache::forget('masterdata');
    }

    public static function clearCacheAllTable()
    {
      foreach (Consts::MASTERDATA_TABLES as $table) {
        Cache::forget("masterdata_$table");
        Cache::forget('dataVersion');
        Cache::forget('masterdata');
      }
    }

    private static function getReasons()
    {
        return Reason::where('static_reason', Consts::TRUE)->get();
    }

    private static function getGames()
    {
        return Game::with(['available_types', 'servers', 'ranks', 'platforms'])->get();
    }

    private static function getLanguages()
    {
        return DB::table('languages')->get();
    }

    private static function getSettings()
    {
        $settings = DB::table('settings')->get();

        $ignoreKeys = [
            Consts::MATTERMOST_TEAM_ID_KEY,
            Consts::BOUNTY_FEE_KEY,
            Consts::SESSION_FEE_KEY,
        ];

        return $settings->filter(function ($item) use ($ignoreKeys) {
            return !in_array($item->key, $ignoreKeys);
        })->values();
    }
}
