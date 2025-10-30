<?php

namespace App\Utils;

use Cache;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\UserOnlineUtils;
use App\Utils\CurrencyExchange;
use App\Models\GameStatistic;
use App\Models\GameProfileOffer;
use App\Http\Services\MasterdataService;
use Exception;

class GameStatisticUtils
{
    public static function createNewGameStatistic($gameProfile)
    {
        $userId = $gameProfile->user_id;
        $gameId = $gameProfile->game_id;

        $gameStatistic = GameStatistic::firstOrNew([
            'game_id' => $gameId
        ]);

        $userIds = $gameStatistic->user_ids;

        if (static::checkExistsUserId($userIds, $userId)) {
            return $gameStatistic;
        }

        $gameStatistic->user_ids = static::getUserIds((array) $userIds, $userId);
        $gameStatistic->executed_date = now();
        $gameStatistic->save();

        static::removeCacheData();

        return $gameStatistic;
    }

    private static function getUserIds($userIds, $userId)
    {
        return collect($userIds)->push($userId)->filter()->unique()->toArray();
    }

    public static function updateForSessioncompleted($session, $gameStatistic = null)
    {
        $gameId = $session->gameProfile->game_id;

        $gameStatistic = $gameStatistic ?? GameStatistic::find($gameId);

        $gameStatistic->total_sessions = BigNumber::new($gameStatistic->total_sessions)->add(1)->toString();

        $offer = static::getGameProfileOffer($session);
        if (!empty($offer)) {
            $gameStatistic = static::calculateTotalBalance($gameStatistic, $session, $offer);
            $gameStatistic = static::calculateTotalQuantity($gameStatistic, $session, $offer);
        }

        $gameStatistic->executed_date = now();
        $gameStatistic->save();

        static::removeCacheData();

        return $gameStatistic;
    }

    private static function calculateTotalBalance($gameStatistic, $session, $offer)
    {
        $coinReceived = static::calculateCoinReceived($offer, $session->quantity_played);
        $gameStatistic->total_coins = BigNumber::new($gameStatistic->total_coins)->add($coinReceived)->toString();

        $barReceived = CurrencyExchange::coinToBar($coinReceived);
        $gameStatistic->total_bars = BigNumber::new($gameStatistic->total_bars)->add($barReceived)->toString();

        return $gameStatistic;
    }

    private static function calculateTotalQuantity($gameStatistic, $session, $offer)
    {
        if ($offer->type === Consts::GAME_TYPE_HOUR) {
            $gameStatistic->total_quantity_per_hour = BigNumber::new($gameStatistic->total_quantity_per_hour)->add($session->quantity_played)->toString();
        }

        if ($offer->type === Consts::GAME_TYPE_PER_GAME) {
            $gameStatistic->total_quantity_per_game = BigNumber::new($gameStatistic->total_quantity_per_game)->add($session->quantity_played)->toString();
        }

        return $gameStatistic;
    }

    private static function getGameProfileOffer($session)
    {
        $offer = GameProfileOffer::withTrashed()->find($session->offer_id);
        // if (empty($offer)) {
        //     throw new Exception('Game profile offer not exists.');
        // }

        return $offer;
    }

    private static function calculateCoinReceived($offer, $quantity)
    {
        return BigNumber::new($offer->price)->div($offer->quantity)->mul($quantity)->toString();
    }

    private static function checkExistsUserId($userIds, $userId)
    {
        if (empty($userIds)) {
            return false;
        }

        return in_array($userId, $userIds);
    }

    public static function getGameStatistics()
    {
        $gameStatistics = static::getCacheData();

        if (!empty($gameStatistics)) {
            return static::modifyGameStatistics($gameStatistics);
        }

        $gameStatistics = GameStatistic::all();
        $gameStatistics = static::saveToCache($gameStatistics);

        return static::modifyGameStatistics($gameStatistics);
    }

    private static function modifyGameStatistics($gameStatistics)
    {
        $games = MasterdataService::getOneTable('games');
        $userIdOnlines = UserOnlineUtils::getUserIdOnlines();

        $result = [];
        $games->each(function ($game) use (&$result, $gameStatistics, $userIdOnlines) {
            $gameStatistic = $gameStatistics->firstWhere('game_id', $game->id);

            list($totalUser, $totalUserOnline) = static::calculateTotalUser($gameStatistic, $userIdOnlines);

            $result[] = [
                'game_id' => $game->id,
                'total_user' => $totalUser,
                'total_user_online' => $totalUserOnline,
                'total_videos' => $gameStatistic ? $gameStatistic->total_videos : 0
            ];
        });

        return $result;
    }

    private static function calculateTotalUser($gameStatistic, $userIdOnlines)
    {
        $totalUser = 0;
        $totalUserOnline = 0;

        if (!empty($gameStatistic)) {
            $userIds = $gameStatistic->user_ids;

            $userIdOnlineOfGames = [];
            collect($userIds)->each(function ($userId) use ($userIdOnlines, &$userIdOnlineOfGames) {
                if (in_array($userId, $userIdOnlines)) {
                    $userIdOnlineOfGames[] = $userId;
                }
            });

            $totalUser = count($userIds);
            $totalUserOnline = count($userIdOnlineOfGames);
        }

        return [$totalUser, $totalUserOnline];
    }

    private static function saveToCache($data)
    {
        $key = static::getKey();
        Cache::forever($key, $data);

        return static::getCacheData();
    }

    private static function getCacheData()
    {
        $key = static::getKey();

        $cacheData = [];
        if (Cache::has($key)) {
            $cacheData = Cache::get($key);
        }

        return $cacheData;
    }

    private static function removeCacheData()
    {
        $key = static::getKey();

        if (Cache::has($key)) {
            Cache::forget($key);
        }
    }

    private static function getKey()
    {
        return 'GameStatistics';
    }
}
