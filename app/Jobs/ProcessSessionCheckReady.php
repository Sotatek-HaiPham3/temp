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

// add      ====> accept schedule
// remove   ====> reject booking, cancel booking, 2 user ready
// update   ====> starting session, first user ready
class ProcessSessionCheckReady implements ShouldQueue
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
        $this->loadSessionsRunning();

        while (true) {
            $this->process();
            usleep($this->interval);
        }
    }

    private function getSessionKeys()
    {
        $key = ProcessSessionCheckReady::getSessionKey('*', '*');
        return $this->redis->keys($key);
    }

    private function process()
    {
        $sessionKeys = $this->getSessionKeys();

        $now = ProcessSessionCheckReady::currentTime();
        ProcessSessionCheckReady::logConsole('timestamp', $now);

        foreach ($sessionKeys as $key) {
            $session = json_decode($this->redis->get($key));

            ProcessSessionCheckReady::logConsole('Do Check ready...', "id = {$session->id}");
            if ($this->canNotifyEmailTime($session, $now) && !$session->hasSendNotifyEmail) {
                ProcessSessionCheckReady::log('Sending starting soon...', "id = {$session->id}");
                $this->sessionService->sendNotifyEmailStartingSession($session);

                ProcessSessionCheckReady::updateSession(Session::find($session->id));
                ProcessSessionCheckReady::log('Sended starting soon...', "id = {$session->id}");
            }

            $startAt = Utils::millisecondsToCarbon($session->schedule_at ?? $session->start_at);
            if ($startAt->timestamp > $now) {
                continue;
            }

            $lastTimeProcessedKey = ProcessSessionCheckReady::lastTimeBountyProcessed();
            $this->redis->set($lastTimeProcessedKey, $now);

            ProcessSessionCheckReady::logConsole('Checking ready...', "id = {$session->id}");

            DB::beginTransaction();
            try {
                if ($session->status === Consts::SESSION_STATUS_ACCEPTED) {
                    ProcessSessionCheckReady::log('Do start...', "id = {$session->id}");
                    $this->sessionService->startingScheduleSession($session->id);
                    DB::commit();
                    ProcessSessionCheckReady::log('Starting...', "id = {$session->id}");
                    continue;
                }

                $startReady = Utils::millisecondsToCarbon($session->start_at ?? $session->schedule_at);
                $expiredTime = $startReady->copy()->addSeconds(Consts::SESSION_CHECK_READY_EXPIRED_TIME)->timestamp;
                ProcessSessionCheckReady::logConsole('Checking expired time...', "expiredTime = {$expiredTime}");
                if ($now > $expiredTime) {
                    ProcessSessionCheckReady::log('Expiring...', "id = {$session->id}");
                    $this->sessionService->handleUserAbsent($session->id);
                    $this->redis->del($key);
                    ProcessSessionCheckReady::log('Expired Confirmation Time', "id = {$session->id}");
                }

                DB::commit();
            } catch (Exception $exception) {
                DB::rollback();
                ProcessSessionCheckReady::log($exception);

                $jsonData = json_encode($session);
                static::removeSession($session);
                static::log('Exception with Session: ' . $jsonData);

                $content = "Data: {$jsonData} \n ";
                $ex = "{$exception->getMessage()} at {$exception->getFile()}:{$exception->getLine()} {$exception->getTraceAsString()}";
                SystemNotification::sendExceptionEmail("{$content} {$ex}");
            }
        }
    }

    private function loadSessionsRunning()
    {
        Session::whereIn('status', [Consts::SESSION_STATUS_ACCEPTED, Consts::SESSION_STATUS_STARTING])
            ->orderBy('schedule_at', 'asc')
            ->get()
            ->map(function ($session) {
                return ProcessSessionCheckReady::addSession($session);
            });
    }

    public static function addSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $sessionKey = ProcessSessionCheckReady::getSessionKey($session->id, $session->booked_at);

        $session->hasSendNotifyEmail = false;
        if (ProcessSessionCheckReady::canNotifyEmailTime($session)) {
            $session->hasSendNotifyEmail = true;
        }

        $redis->set($sessionKey, $session);

        return $session;
    }

    private static function canNotifyEmailTime($session, $now = null)
    {
        if (!$now) {
            $now = ProcessSessionCheckReady::currentTime();
        }

        $notifyEmailTime = Utils::millisecondsToCarbon($session->schedule_at)
            ->subSeconds(Consts::SESSION_CHECK_READY_STARTING_TIME * 60)
            ->timestamp;

        return $now > $notifyEmailTime;
    }

    public static function updateSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = ProcessSessionCheckReady::getSessionKey($session->id, '*');

        foreach ($redis->keys($key) as $key) {
            $redis->del($key);
        }

        ProcessSessionCheckReady::addSession($session);
    }

    public static function removeSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = ProcessSessionCheckReady::getSessionKey($session->id, $session->booked_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getSessionKey($sessionId, $endTime)
    {
        return "session_check_ready_{$sessionId}_{$endTime}";
    }

    static function lastTimeBountyProcessed()
    {
        return 'last_time_session_check_ready_processed';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_SESSION_CHECK_READY;
    }

    static function log(...$params)
    {
        logger('====ProcessSessionCheckReady: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
