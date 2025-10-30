<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\ReviewTagStatistic;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;

class CalculateReviewTag implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $review;
    private $tagId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($review, $tagId)
    {
        $this->review = $review;
        $this->tagId = $tagId;
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
        $userId = $this->review->user_id;
        $reviewType = $this->review->object_type;

        $statistic = ReviewTagStatistic::firstOrCreate([
            'user_id' => $userId,
            'review_tag_id' => $this->tagId,
            'review_type' => $reviewType
        ]);

        $statistic->quantity = BigNumber::new($statistic->quantity)->add(1)->toString();
        $statistic->executed_date = now();
        $statistic->save();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
    }

    private function log(...$params)
    {
        logger('====CalculateReviewTag: ', [$params]);
    }
}
