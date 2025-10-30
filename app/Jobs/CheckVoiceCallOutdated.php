<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Services\VoiceService;
use App\Models\VoiceDiary;
use App\Consts;
use App\Utils;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class CheckVoiceCallOutdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $voiceService;

    protected $redis;

    protected $interval;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->voiceService = new VoiceService();
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
        $this->loadVoicesCalling();

        while (true) {
            $timestamp = CheckVoiceCallOutdated::currentTime();
            $this->process($timestamp);
            usleep($this->interval);
        }
    }

    private function process($timestamp)
    {
        CheckVoiceCallOutdated::logConsole('timestamp', CheckVoiceCallOutdated::currentTime());

        $key = CheckVoiceCallOutdated::getVoiceKey('*', '*');
        $voiceKeys = $this->redis->keys($key);

        foreach ($voiceKeys as $key) {
            $voice = json_decode($this->redis->get($key));

            CheckVoiceCallOutdated::logConsole('Do checking...', "id = {$voice->id}");

            $updatedAt = Utils::dateTimeToMilliseconds($voice->updated_at);
            $expiredTime = Utils::millisecondsToCarbon($updatedAt)->addSeconds(Consts::VOICE_CALLING_EXPIRED_TIME)->timestamp;
            if ($expiredTime > $timestamp) {
                continue;
            }

            DB::beginTransaction();
            try {
                CheckVoiceCallOutdated::log('Declining...', "id = {$voice->id}");
                $this->voiceService->declineCall($voice->hash);
                CheckVoiceCallOutdated::log('declined', "id = {$voice->id}");
                DB::commit();
            } catch (Exception $ex) {
                DB::rollback();
                CheckVoiceCallOutdated::log($ex);
            }
        }
    }

    private function loadVoicesCalling()
    {
        VoiceDiary::where('status', Consts::VOICE_STATUS_CALLING)
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(function ($voice) {
                return CheckVoiceCallOutdated::addVoice($voice);
            });
    }

    public static function addVoice($voice)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $voiceKey = CheckVoiceCallOutdated::getVoiceKey($voice->id, $voice->created_at);

        $redis->set($voiceKey, $voice);

        return $voice;
    }

    public static function removeVoice($voice)
    {
        $redis = Redis::connection(static::getRedisConnection());

        $key = CheckVoiceCallOutdated::getVoiceKey($voice->id, $voice->created_at);
        $redis->del($key);
    }

    static function currentTime()
    {
        return now()->timestamp;
    }

    static function getVoiceKey($voiceId, $startTime)
    {
        return "voice_calling_{$voiceId}_{$startTime}";
    }

    private static function getRedisConnection()
    {
        return Consts::RC_CHECK_VOICE_OUTDATED;
    }

    static function log(...$params)
    {
        logger('====CheckVoiceCallOutdated: ', $params);
    }

    static function logConsole(...$params)
    {
        $message = json_encode($params);
        echo "$message \n";
    }
}
