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
use App\Utils\BigNumber;
use Carbon\Carbon;
use Exception;

// add      => accept book now hour, 2 user ready
// remove   => stop session, complete session
// update   => accept request add more
class ProcessSessionCompleted implements ShouldQueue
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
        $key = ProcessSessionCompleted::getSessionKey('*', '*');
        return $this->redis->keys($key);
    }

    private function process()
    {
        $sessionKeys = $this->getSessionKeys();

        $now = ProcessSessionCompleted::currentTime();
        ProcessSessionCompleted::logConsole('timestamp', $now);

        foreach ($sessionKeys as $key) {
            $session = json_decode($this->redis->get($key));

            $quantityHours = $session->quantity ?: 1; // free session default 1 hour
            $quantitySeconds = BigNumber::new($quantityHours)->mul(60)->mul(60)->toString();
            $timeStart = $session->type === Consts::SESSION_TYPE_FREE ? $session->start_at : $session->schedule_at;
            $expiredTime = Utils::millisecondsToCarbon($timeStart)->addSeconds($quantitySeconds)->timestamp;

            ProcessSessionCompleted::logConsole('Do complete...', "id = {$session->id}", "expiredTime = {$expiredTime}");

            if ($expiredTime > $now) {
                continue;
            }

            $lastTimeProcessedKey = ProcessSessionCompleted::lastTimeBountyProcessed();
            $this->redis->set($lastTimeProcessedKey, $now);

            DB::beginTransaction();
            try {
                ProcessSessionCompleted::log('Completing...', "id = {$session->id}");
                $this->sessionService->completeSession($session->id);
                ProcessSessionCompleted::log('Completed', "id = {$session->id}");
                DB::commit();
            } catch (Exception $exception) {
                DB::rollback();
                ProcessSessionCompleted::log($exception);

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
        Session::whereIn('status', [Consts::SESSION_STATUS_RUNNING, Consts::SESSION_STATUS_MARK_COMPLETED])
            ->orderBy('start_at', 'asc')
            ->get()
            ->map(function ($session) {
                return ProcessSessionCompleted::addSession($session);
            });
    }

    public static function addSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $sessionKey = ProcessSessionCompleted::getSessionKey($session->id, $session->booked_at);

        $redis->set($sessionKey, $session);

        return $session;
    }

    public static function updateSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = ProcessSessionCompleted::getSessionKey($session->id, '*');

        foreach ($redis->keys($key) as $key) {
            $redis->del($key);
        }

        ProcessSessionCompleted::addSession($session);
    }

    public static function removeSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = ProcessSessionCompleted::getSessionKey($session->id, $session->booked_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getSessionKey($sessionId, $endTime)
    {
        return "session_running_{$sessionId}_{$endTime}";
    }

    static function lastTimeBountyProcessed()
    {
        return 'last_time_session_running_processed';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_PROCESS_SESSION_RUNNING;
    }

    static function log(...$params)
    {
        logger('====ProcessSessionCompleted: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
