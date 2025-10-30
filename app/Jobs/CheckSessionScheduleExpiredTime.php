<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Http\Services\SessionService;
use App\Http\Services\SystemNotification;
use App\Models\Session;
use App\Consts;
use App\Utils;
use Exception;
use Carbon\Carbon;

// add      => book schedule
// remove   => accept booking, reject booking, cancel booking
class CheckSessionScheduleExpiredTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionService;

    protected $redis;

    protected $interval;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->sessionService = new SessionService();
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
        $this->loadSessionsBooked();

        while (true) {
            $this->process();
            usleep($this->interval);
        }
    }

    private function getSessionKeys()
    {
        $key = CheckSessionScheduleExpiredTime::getSessionKey('*', '*');
        return $this->redis->keys($key);
    }

    private function process()
    {
        $sessionKeys = $this->getSessionKeys();

        $now = CheckSessionScheduleExpiredTime::currentTime();
        CheckSessionScheduleExpiredTime::logConsole('timestamp', $now);

        foreach ($sessionKeys as $key) {
            $session = json_decode($this->redis->get($key));

            $lastTimeProcessedKey = CheckSessionScheduleExpiredTime::lastTimeBountyProcessed();
            $this->redis->set($lastTimeProcessedKey, $now);

            CheckSessionScheduleExpiredTime::logConsole('Do checking...', "id = {$session->id}");
            $scheduleAt = Utils::millisecondsToCarbon($session->schedule_at)->timestamp;

            if ($scheduleAt > $now) {
                continue;
            }
            DB::beginTransaction();
            try {
                CheckSessionScheduleExpiredTime::log('Rejecting', "id = {$session->id}");
                $params = ['content' => Consts::REASON_CONTENT_OUTDATED];
                $this->sessionService->rejectBookingGameProfile($session->id, $params, Consts::TRUE);
                CheckSessionScheduleExpiredTime::log('Rejected', "id = {$session->id}");
                DB::commit();
            } catch (Exception $exception) {
                DB::rollback();
                CheckSessionScheduleExpiredTime::log($exception);

                $jsonData = json_encode($session);
                static::removeSession($session);
                static::log('Exception with Session: ' . $jsonData);

                $content = "Data: {$jsonData} \n ";
                $ex = "{$exception->getMessage()} at {$exception->getFile()}:{$exception->getLine()} {$exception->getTraceAsString()}";
                SystemNotification::sendExceptionEmail("{$content} {$ex}");
            }
        }
    }

    private function loadSessionsBooked()
    {
        Session::where('status', Consts::SESSION_STATUS_BOOKED)
            ->whereColumn('booked_at', '!=', 'schedule_at')
            ->orderBy('schedule_at', 'asc')
            ->get()
            ->map(function ($session) {
                return CheckSessionScheduleExpiredTime::addSession($session);
            });
    }

    public static function addSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $sessionKey = CheckSessionScheduleExpiredTime::getSessionKey($session->id, $session->booked_at);

        $redis->set($sessionKey, $session);

        return $session;
    }

    public static function removeSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = CheckSessionScheduleExpiredTime::getSessionKey($session->id, $session->booked_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getSessionKey($sessionId, $endTime)
    {
        return "session_check_schedule_expired_time_{$sessionId}_{$endTime}";
    }

    static function lastTimeBountyProcessed()
    {
        return 'last_time_check_session_check_schedule_expired_time';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_CHECK_SESSION_SCHEDULE_EXPIRED_TIME;
    }

    static function log(...$params)
    {
        logger('====CheckSessionScheduleExpiredTime: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
