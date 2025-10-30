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
use App\Http\Services\SystemNotification;
use App\Http\Services\SessionService;
use App\Models\Session;
use App\Consts;
use App\Utils;
use Exception;
use Carbon\Carbon;

// add      => booking not schedule
// remove   => accept booking, reject booking, cancel booking
class CheckSessionResponseInvitation implements ShouldQueue
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
        $key = CheckSessionResponseInvitation::getSessionKey('*', '*');
        return $this->redis->keys($key);
    }

    private function process()
    {
        $sessionKeys = $this->getSessionKeys();

        $now = CheckSessionResponseInvitation::currentTime();
        CheckSessionResponseInvitation::logConsole('timestamp', $now);

        foreach ($sessionKeys as $key) {
            $session = json_decode($this->redis->get($key));

            $lastTimeProcessedKey = CheckSessionResponseInvitation::lastTimeBountyProcessed();
            $this->redis->set($lastTimeProcessedKey, $now);

            CheckSessionResponseInvitation::logConsole('Do checking...', "id = {$session->id}");

            DB::beginTransaction();
            try {
                $expiredTime = Utils::millisecondsToCarbon($session->booked_at)->addSeconds(Consts::GAMEPROFILE_BOOK_NOW_AUTO_CANCEL)->timestamp;
                if ($now > $expiredTime) {
                    CheckSessionResponseInvitation::log('Rejecting', "id = {$session->id}");
                    $params = ['content' => Consts::REASON_CONTENT_OUTDATED];
                    $this->sessionService->rejectBookingGameProfile($session->id, $params, Consts::TRUE);
                    CheckSessionResponseInvitation::log('Rejected', "id = {$session->id}");
                    DB::commit();
                    continue;
                }

                DB::commit();
            } catch (Exception $exception) {
                DB::rollback();
                CheckSessionResponseInvitation::log($exception);

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
            ->whereIn('type', [Consts::SESSION_TYPE_NOW, Consts::SESSION_TYPE_SCHEDULE])
            ->orderBy('schedule_at', 'asc')
            ->get()
            ->map(function ($session) {
                return CheckSessionResponseInvitation::addSession($session);
            });
    }

    public static function addSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $sessionKey = CheckSessionResponseInvitation::getSessionKey($session->id, $session->booked_at);

        $redis->set($sessionKey, $session);

        return $session;
    }

    public static function updateSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = CheckSessionResponseInvitation::getSessionKey($session->id, '*');

        foreach ($redis->keys($key) as $key) {
            $redis->del($key);
        }

        CheckSessionResponseInvitation::addSession($session);
    }

    public static function removeSession($session)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = CheckSessionResponseInvitation::getSessionKey($session->id, $session->booked_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getSessionKey($sessionId, $endTime)
    {
        return "session_response_invitation_{$sessionId}_{$endTime}";
    }

    static function lastTimeBountyProcessed()
    {
        return 'last_time_check_session_book_now_processed';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_CHECK_SESSION_BOOK_NOW;
    }

    static function log(...$params)
    {
        logger('====CheckSessionResponseInvitation: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
