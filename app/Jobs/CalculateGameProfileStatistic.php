<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Models\SessionReview;
use App\Models\GameProfile;
use App\Models\GameProfileOffer;
use App\Models\GameProfileStatistic;
use App\Models\Session;
use App\Models\UserStatistic;
use App\Events\GameProfileUpdated;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use DB;

class CalculateGameProfileStatistic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $gameProfileId;
    protected $currentTime;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($gameProfileId = null)
    {
        $this->gameProfileId = $gameProfileId;
        $this->currentTime = Utils::currentMilliseconds();
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
            $this->process();
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollback();
            $this->log($exception);
        }
    }

    private function process()
    {
        $key = $this->getKey();
        $cacheData = $this->getCacheData();

        if ($this->gameProfileId) {
            $this->processForAction();
        } else {
            $this->processForFirstTime();
        }

        Cache::forever($key, $cacheData);
    }

    private function processForAction()
    {
        $gameProfileId = $this->gameProfileId;
        $this->saveSessionStatistic($gameProfileId);
        $this->saveTotalSessionRating($gameProfileId);

        $cacheData = $this->getCacheData();
        $cacheData[$gameProfileId] = $this->currentTime;
    }

    private function processForFirstTime()
    {
        $cacheData = $this->getCacheData();
        $gameProfileIds = GameProfile::withTrashed()->pluck('id');

        foreach ($gameProfileIds as $id) {
            $this->saveSessionStatistic($id);
            $cacheData[$id] = $this->currentTime;
        }
        $this->saveTotalSessionRating();
    }

    private function saveSessionStatistic($gameProfileId)
    {
        $gameProfileStatistic = GameProfileStatistic::firstOrCreate(['game_profile_id' => $gameProfileId]);
        $rateResult = $this->calculateRate($gameProfileStatistic, $gameProfileId);
        $playedResult = $this->calculatePlayed($gameProfileStatistic, $gameProfileId);

        $gameProfileStatistic->rating = $rateResult['rating'];
        $gameProfileStatistic->total_review = $rateResult['total_review'];
        $gameProfileStatistic->recommend = $rateResult['recommend'];
        $gameProfileStatistic->unrecommend = $rateResult['unrecommend'];
        $gameProfileStatistic->total_played = $playedResult['total_played'];
        $gameProfileStatistic->hour_played = $playedResult['hour_played'];
        $gameProfileStatistic->game_played = $playedResult['game_played'];
        $gameProfileStatistic->executed_date = now();
        $gameProfileStatistic->save();

        // event(new GameProfileUpdated($gameProfileId));
    }

    private function saveTotalSessionRating($gameProfileId = null)
    {
        $userId = GameProfile::where('id', $gameProfileId)->value('user_id');
        $gameProfileStatistic = GameProfileStatistic::join('game_profiles', 'game_profiles.id', 'game_profile_statistics.game_profile_id')
            ->select('game_profiles.user_id', 'game_profile_statistics.rating', 'game_profile_statistics.total_review', 'game_profile_statistics.total_played')
            ->when(!empty($userId), function ($query) use ($userId) {
                $query->where('game_profiles.user_id', $userId);
            })
            ->get();

        if ($userId) {
            $this->calculateTotalSession($userId, $gameProfileStatistic);
        } else {
            $users = [];
            foreach ($gameProfileStatistic as $key => $value) {
                $users[$value->user_id][] = $value;
            }

            foreach ($users as $userKey => $statistic) {
                $this->calculateTotalSession($userKey, $statistic);
            }
        }
    }

    private function calculateTotalSession($userId, $profileStatistic)
    {
        $sumReview = 0;
        $sumRate = 0;
        $totalPlayed = 0;
        foreach ($profileStatistic as $sessionStatistic) {
            $sumReview = BigNumber::new($sumReview)->add($sessionStatistic->total_review);
            $totalRate = BigNumber::new($sessionStatistic->total_review)->mul($sessionStatistic->rating)->toString();
            $sumRate = BigNumber::new($sumRate)->add($totalRate);
            $totalPlayed = BigNumber::new($totalPlayed)->add($sessionStatistic->total_played);
        }

        $statistic = UserStatistic::firstOrNew(['user_id' => $userId]);
        $statistic->session_reviewers = $sumReview;
        $statistic->session_rating = BigNumber::new($sumRate)->comp(0) > 0 ? BigNumber::new($sumRate)->div($sumReview)->toString() : 0;
        $statistic->session_played = $totalPlayed;
        $statistic->save();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
    }

    private function calculateRate($gameProfileStatistic, $gameProfileId)
    {
        $cacheData = $this->getCacheData();
        $hasCache = !empty($cacheData[$gameProfileId]);
        $userId = GameProfile::where('id', $gameProfileId)->value('user_id');

        $rates = SessionReview::where('submit_at', '<=', $this->currentTime)
            ->when($hasCache, function ($query) use ($cacheData, $gameProfileId) {
                $query->where('submit_at', '>', $cacheData[$gameProfileId]);
            })
            ->where('game_profile_id', $gameProfileId)
            ->where('user_id', $userId)
            ->orderBy('updated_at')
            ->get();

        $totalNumReviewers = $rates->count();
        $totalCountRating = $rates->sum('rate');
        $totalRecommend = $rates->where('recommend', Consts::TRUE)->count();
        $totalUnrecommend = $rates->where('recommend', Consts::FALSE)->count();

        if ($hasCache) {
            $totalNumReviewers = BigNumber::new($totalNumReviewers)->add($gameProfileStatistic->total_review)->toString();

            $currentCountRating = BigNumber::new($gameProfileStatistic->total_review)->mul($gameProfileStatistic->rating)->toString();
            $totalCountRating = BigNumber::new($totalCountRating)->add($currentCountRating)->toString();

            $totalRecommend = BigNumber::new($totalRecommend)->add($gameProfileStatistic->recommend)->toString();
            $totalUnrecommend = BigNumber::new($totalUnrecommend)->add($gameProfileStatistic->unrecommend)->toString();
        }

        return [
            'rating' => BigNumber::new($totalCountRating)->comp(0) > 0 ? BigNumber::new($totalCountRating)->div($totalNumReviewers)->toString() : 0,
            'total_review' => $totalNumReviewers,
            'recommend' => $totalRecommend,
            'unrecommend' => $totalUnrecommend
        ];
    }

    private function calculatePlayed($gameProfileStatistic, $gameProfileId)
    {
        $cacheData = $this->getCacheData();
        $hasCache = !empty($cacheData[$gameProfileId]);
        $sessions = Session::where('game_profile_id', $gameProfileId)
            ->whereIn('status', [Consts::SESSION_STATUS_STOPPED, Consts::SESSION_STATUS_COMPLETED])
            ->when(!empty($hasCache), function ($query) use ($cacheData, $gameProfileId) {
                $query->where('end_at', '>', $cacheData[$gameProfileId]);
            })
            ->where('end_at', '<=', $this->currentTime)
            ->get();

        $timePlayed = $this->calculateTimePlayed($sessions);

        $hourPlayed = $timePlayed['hour'];
        $gamePlayed = $timePlayed['game'];
        $totalPlayed = $sessions->count();

        if (!empty($hasCache)) {
            $totalPlayed = BigNumber::new($totalPlayed)->add($gameProfileStatistic->total_played)->toString();
            $hourPlayed = BigNumber::new($hourPlayed)->add($gameProfileStatistic->hour_played)->toString();
            $gamePlayed = BigNumber::new($gamePlayed)->add($gameProfileStatistic->game_played)->toString();
        }

        return [
            'total_played' => $totalPlayed,
            'hour_played' => $hourPlayed,
            'game_played' => $gamePlayed
        ];
    }

    private function calculateTimePlayed($sessions)
    {
        $hour = 0;
        $game = 0;

        $offerIds = collect($sessions)->pluck('offer_id')->toArray();
        $offers = GameProfileOffer::withTrashed()
            ->whereIn('id', $offerIds)
            ->get()
            ->mapWithKeys(function ($offer) {
                return [$offer['id'] => $offer];
            })
            ->all();
        foreach ($sessions as $session) {
            if ($session->type === Consts::SESSION_TYPE_FREE || empty($offers[$session->offer_id])) {
                continue;
            }

            $offer = $offers[$session->offer_id];
            if ($offer->type === Consts::GAME_TYPE_HOUR) {
                $hour = BigNumber::new($hour)->add($session->quantity_played)->toString();
                continue;
            }
            $game = BigNumber::new($game)->add($session->quantity_played)->toString();
        }

        return [
            'hour' => $hour,
            'game' => $game
        ];
    }

    private function getCacheData()
    {
        $key = $this->getKey();
        if (Cache::has($key)) {
            $cacheData = Cache::get($key);
        }

        if (empty($cacheData[$this->gameProfileId])) {
            $cacheData[$this->gameProfileId] = 0;
        }

        return $cacheData;
    }

    private function getKey()
    {
        return 'CalculateGameProfileStatistic';
    }

    private function log(...$params)
    {
        logger('====CalculateGameProfileStatistic: ', [$params]);
    }
}
