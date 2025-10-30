<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use App\Models\UserStatistic;
use App\Models\GameProfileStatistic;
use App\Models\ReviewTagStatistic;
use App\Models\SessionReviewTag;
use App\Models\GameProfile;
use App\Utils\BigNumber;
use App\Events\UserUpdated;
use App\Events\GameProfileUpdated;
use App\Consts;
use DB;

class CalculateUserRatingWhenRemoveReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $review;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($userId, $review)
    {
        $this->userId = $userId;
        $this->review = $review;
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
        $userStatistic = UserStatistic::where('user_id', $this->userId)->first();

        $res = $this->calculateUserStatistic($userStatistic);

        $userStatistic->total_reviewers = $res['total_reviewers'];
        $userStatistic->rating = $res['rating'];
        $userStatistic->session_rating = $res['session_rating'];
        $userStatistic->session_reviewers = $res['session_reviewers'];
        $userStatistic->save();

        $this->calculateReviewTag();
        event(new UserUpdated($this->userId));

        $isReviewGamelancer = GameProfile::withTrashed()
            ->where('id', $this->review->game_profile_id)
            ->where('user_id', $this->userId)
            ->exists();
        if ($this->review->object_type === Consts::OBJECT_TYPE_SESSION && $this->review->game_profile_id && $isReviewGamelancer) {
            // game profile statistic
            $this->calculateGameProfileStatistic($this->review->game_profile_id);
        }
    }

    private function calculateReviewTag()
    {
        $reviewTags = SessionReviewTag::where('review_id', $this->review->id)->get();
        foreach ($reviewTags as $tag) {
            $statistic = ReviewTagStatistic::firstOrCreate([
                'user_id' => $this->userId,
                'review_tag_id' => $tag->review_tag_id,
                'review_type' => $this->review->object_type
            ]);
            $statistic->quantity = $statistic->quantity ? $statistic->quantity - 1 : 0;
            $statistic->executed_date = now();
            $statistic->save();
        }
    }

    private function calculateUserStatistic($statistic)
    {
        // total user rating
        $currentNumReviewers = $statistic->total_reviewers;
        $currentCountRating = BigNumber::new($statistic->total_reviewers)->mul($statistic->rating)->toString();
        $totalCountRating = BigNumber::new($currentCountRating)->sub($this->review->rate)->toString();
        $totalNumReviewers = BigNumber::new($currentNumReviewers)->sub(1)->toString();

        // total session rating
        $currentSessionReviewers = $statistic->session_reviewers;
        $currentCountSessionRating = BigNumber::new($statistic->session_reviewers)->mul($statistic->session_rating)->toString();
        $totalCountSessionRating = BigNumber::new($currentCountSessionRating)->sub($this->review->rate)->toString();
        $totalSessionReviewers = BigNumber::new($currentSessionReviewers)->sub(1)->toString();

        return [
            'total_reviewers'   => $totalNumReviewers,
            'rating'            => $totalNumReviewers ? BigNumber::new($totalCountRating)->div($totalNumReviewers)->toString() : Consts::FALSE,
            'session_rating'    => $totalSessionReviewers ? BigNumber::new($totalCountSessionRating)->div($totalSessionReviewers)->toString() : Consts::FALSE,
            'session_reviewers' => $totalSessionReviewers
        ];
    }

    private function calculateRateGameProfile($gameProfileStatistic)
    {
        $currentNumReviewers = $gameProfileStatistic->total_review;
        $currentCountRating = BigNumber::new($gameProfileStatistic->total_review)->mul($gameProfileStatistic->rating)->toString();

        $totalCountRating = BigNumber::new($currentCountRating)->sub($this->review->rate)->toString();
        $totalNumReviewers = BigNumber::new($currentNumReviewers)->sub(1)->toString();

        $recommend = $this->review->recommend === Consts::TRUE ? BigNumber::new($gameProfileStatistic->recommend)->sub(1)->toString() : $gameProfileStatistic->recommend;
        $unrecommend = $this->review->unrecommend === Consts::FALSE ? BigNumber::new($gameProfileStatistic->unrecommend)->sub(1)->toString() : $gameProfileStatistic->unrecommend;

        return [
            'total_review' => $totalNumReviewers,
            'rating'       => $totalNumReviewers ? BigNumber::new($totalCountRating)->div($totalNumReviewers)->toString() : Consts::FALSE,
            'recommend'    => $recommend,
            'unrecommend'  => $unrecommend
        ];
    }

    private function calculateGameProfileStatistic($gameProfileId)
    {
        $gameProfileStatistic = GameProfileStatistic::where('game_profile_id', $gameProfileId)->first();
        $res = $this->calculateRateGameProfile($gameProfileStatistic);

        $gameProfileStatistic->total_review = $res['total_review'];
        $gameProfileStatistic->rating = $res['rating'];
        $gameProfileStatistic->recommend = $res['recommend'];
        $gameProfileStatistic->unrecommend = $res['unrecommend'];
        $gameProfileStatistic->executed_date = now();
        $gameProfileStatistic->save();

        event(new GameProfileUpdated($gameProfileId));
    }

    private function log(...$params)
    {
        logger('====CalculateWhenRemoveReview: ', [$params]);
    }
}
