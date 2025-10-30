<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\VideoUpdated;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client as GuzzleClient;

class VideoUpdatingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $redis;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct()
    {
        $this->redis = Redis::connection(static::getRedisConnection());
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    public function handle()
    {
        $pattern = static::getVideoKey('*');
        $result = $this->redis->keys($pattern);

        foreach ($result as $key) {

            $videoId = $this->redis->get($key);
            $info = $this->getVideoTranscoding($videoId)['data'];

            if (!$info || empty($info['is_finished'])) {
                continue;
            }

            $this->redis->del($key);

            $data = [
                'video_id'      => $videoId,
                'id'            => $info['id'],
                'video_path'    => $info['video_path'],
                'thumbnail'     => $info['thumbnail'],
                'mimetype'      => $info['mimetype']
            ];
            event(new VideoUpdated($data));
        }
    }

    private function getVideoTranscoding($videoId)
    {
        $client = new GuzzleClient();

        $fullUrl = sprintf('%s/api/medias/videos/info', Utils::trimChar(env('APP_URL')));

        $response = $client->request('GET', $fullUrl, [
            'query' => [
                'id' => $videoId,
                'no_topic' => Consts::TRUE
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody(), true);
    }

    public static function addVideoTracking($videoId)
    {
        $key = static::getVideoKey($videoId);
        $redis = Redis::connection(static::getRedisConnection());
        $redis->set($key, $videoId);
    }

    static function getVideoKey($videoId)
    {
        return "video_id_{$videoId}";
    }

    private static function getRedisConnection()
    {
        return Consts::RC_USER_VIDEOS;
    }

}
