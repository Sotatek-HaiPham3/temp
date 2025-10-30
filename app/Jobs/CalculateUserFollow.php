<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\UserFollowing;
use App\Models\UserStatistic;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;
use DB;

class CalculateUserFollow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $followerId;
    private $followingId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($followerId, $followingId)
    {
        $this->followerId = $followerId;
        $this->followingId = $followingId;
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

    public function process()
    {
        $this->calculateFollowing($this->followerId);
        $this->calculateFollower($this->followingId);
    }

    private function calculateFollower($userId)
    {
        $totalFollower = UserFollowing::where('is_following', Consts::TRUE)
            ->where('following_id', $userId)
            ->get()
            ->count();

        $userStatistic = UserStatistic::firstOrNew(['user_id' => $userId]);
        $userStatistic->total_followers = $totalFollower;
        $userStatistic->save();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
    }

    private function calculateFollowing($userId)
    {
        $totalFollowing = UserFollowing::where('is_following', Consts::TRUE)
            ->where('user_id', $userId)
            ->get()
            ->count();

        $userStatistic = UserStatistic::firstOrNew(['user_id' => $userId]);
        $userStatistic->total_following = $totalFollowing;
        $userStatistic->save();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
    }

    private function log(...$params)
    {
        logger('====CalculateUserFollow: ', [$params]);
    }
}
