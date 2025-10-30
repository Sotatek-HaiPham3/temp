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
use App\Models\UserStatistic;
use App\Utils\BigNumber;
use App\Utils;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;
use DB;

class CalculateUserRating implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
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
        $currentTime = Utils::currentMilliseconds();
        $userId = $this->userId;

        $rates = SessionReview::where('submit_at', '>', $cacheData[$userId])
            ->where('submit_at', '<=', $currentTime)
            ->where('user_id', $userId)
            ->orderBy('updated_at')
            ->get();

        $userStatistic = UserStatistic::firstOrNew(['user_id' => $userId]);
        $res = $this->calculateRate($userStatistic, $rates);

        $userStatistic->total_reviewers = $res['total_reviewers'];
        $userStatistic->rating = $res['rating'];
        $userStatistic->save();

        $cacheData[$userId] = $currentTime;

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        Cache::forever($key, $cacheData);
    }

    private function calculateRate($userStatistic, $rates)
    {
        $totalNumReviewers = $rates->count();
        $totalCountRating = $rates->sum('rate');

        $cacheData = $this->getCacheData();
        $userId = $this->userId;

        if (!empty($cacheData[$userId])) {
            $totalNumReviewers = BigNumber::new($totalNumReviewers)->add($userStatistic->total_reviewers)->toString();

            $currentCountRating = BigNumber::new($userStatistic->total_reviewers)->mul($userStatistic->rating)->toString();
            $totalCountRating = BigNumber::new($totalCountRating)->add($currentCountRating)->toString();
        }

        return [
            'total_reviewers' => $totalNumReviewers,
            'rating' => BigNumber::new($totalCountRating)->comp(0) > 0 ? BigNumber::new($totalCountRating)->div($totalNumReviewers)->toString() : 0
        ];
    }

    private function getCacheData()
    {
        $key = $this->getKey();
        if (Cache::has($key)) {
            $cacheData = Cache::get($key);
        }

        if (empty($cacheData[$this->userId])) {
            $cacheData[$this->userId] = 0;
        }

        return $cacheData;
    }

    private function getKey()
    {
        return 'CalculateUserRating';
    }

    private function log(...$params)
    {
        logger('====CalculateUserRating: ', [$params]);
    }
}
