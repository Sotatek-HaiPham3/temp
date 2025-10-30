<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\UserStatistic;
use App\Models\SessionReview;
use App\Jobs\CalculateUserFollow;
use App\Jobs\CalculateUserRating;
use App\Jobs\CalculateGameProfileStatistic;

class RunUserStatistic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:statistic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate user statistic';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->migrateReviewData();
        // user statistic
        $userIds = User::pluck('id');
        Cache::delete('CalculateUserRating');
        Cache::delete('CalculateUserSessionsPlayed');
        foreach ($userIds as $userId) {
            UserStatistic::firstOrCreate(['user_id' => $userId]);

            CalculateUserFollow::dispatchNow($userId, $userId);
            CalculateUserRating::dispatchNow($userId);
        }

        // game profile statistic
        Cache::delete('CalculateGameProfileStatistic');
        CalculateGameProfileStatistic::dispatchNow();
    }

    private function migrateReviewData()
    {
        $reviews = SessionReview::withTrashed()->where('submit_at', 0)->get();
        foreach ($reviews as $review) {
            $submitAt = strtotime($review->created_at) * 1000;
            SessionReview::withTrashed()
                ->where('id', $review->id)
                ->update(['submit_at' => $submitAt]);
        }
    }
}
