<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Services\BountyService;
use App\Models\Bounty;
use App\Consts;
use App\Utils;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// add      => mark complete
// remove   => complete, dispute
class CheckBountyExpiredTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bountyService;

    protected $redis;

    protected $interval;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->bountyService = new BountyService();
        $this->interval = 200000;// 200ms

        $this->redis = Redis::connection(static::getRedisConnection());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->redis->flushDB();
        $this->loadBountiesRunning();

        while (true) {
            $timestamp = CheckBountyExpiredTime::currentTime();
            $this->process($timestamp);
            usleep($this->interval);
        }
    }

    private function process($timestamp)
    {
        CheckBountyExpiredTime::logConsole('timestamp', CheckBountyExpiredTime::currentTime());

        $key = CheckBountyExpiredTime::getBountyKey('*', '*');
        $bountyKeys = $this->redis->keys($key);

        foreach ($bountyKeys as $key) {
            $bounty = json_decode($this->redis->get($key));

            CheckBountyExpiredTime::logConsole('Do checking...', "id = {$bounty->id}");

            $expiredTime = Utils::millisecondsToCarbon($bounty->stopped_at)->addSeconds(Consts::BOUNTY_EXPIRED_TIME)->timestamp;
            if ($expiredTime > $timestamp) {
                continue;
            }

            $lastTimeProcessedKey = CheckBountyExpiredTime::lastTimeBountyProcessed();
            $this->redis->set($lastTimeProcessedKey, $timestamp);

            DB::beginTransaction();
            try {
                CheckBountyExpiredTime::log('Completing...', "id = {$bounty->id}");
                $this->bountyService->completeBounty($bounty->id);
                CheckBountyExpiredTime::log('Completed', "id = {$bounty->id}");
                DB::commit();
            } catch (Exception $ex) {
                DB::rollback();
                CheckBountyExpiredTime::log($ex);
            }
        }
    }

    private function loadBountiesRunning()
    {
        $bounties = Bounty::where('status', Consts::BOUNTY_STATUS_STOPPED)
            ->orderBy('stopped_at', 'asc')
            ->get()
            ->map(function ($bounty) {
                return CheckBountyExpiredTime::addBounty($bounty);
            });
    }

    public static function addBounty($bounty)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $bountyKey = CheckBountyExpiredTime::getBountyKey($bounty->id, $bounty->stopped_at);

        $redis->set($bountyKey, $bounty);

        return $bounty;
    }

    public static function removeBounty($bounty)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = CheckBountyExpiredTime::getBountyKey($bounty->id, $bounty->stopped_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getBountyKey($bountyId, $endTime)
    {
        return "bounty_running_{$bountyId}_{$endTime}";
    }

    static function lastTimeBountyProcessed()
    {
        return 'last_time_bounty_processed';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_CHECK_BOUNTY_EXPIRED_TIME;
    }

    static function log(...$params)
    {
        logger('====CheckBountyExpiredTime: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
