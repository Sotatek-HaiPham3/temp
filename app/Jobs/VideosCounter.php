<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Consts;
use App\Utils\BigNumber;
use Cache;
use Carbon\Carbon;
use Elasticsearch;
use App\Models\GameStatistic;
use App\Http\Services\MasterdataService;

class VideosCounter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $interval;
    protected $isRunning = true;
    protected $isFistTimeRunning = false;

    protected $counter = [];

    const DAFAULT_DATE_TIME = '2019-01-01 00:00:00';
    const DATE_TIME_PATTERN = 'Y-m-d H:i:s';

    const LIMITATION = 1000;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->interval = 200000; // 200ms
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cacheKey = $this->getCacheKey();
        $timestamp = $this->getCacheData($cacheKey);
        $this->isFistTimeRunning = empty($timestamp);

        while (true) {
            $timestamp = $this->getCacheData($cacheKey);

            $lastVideo = $this->process($timestamp ?? static::DAFAULT_DATE_TIME);

            $timestamp = empty($lastVideo['created'])
                ? now()->format(static::DATE_TIME_PATTERN)
                : $lastVideo['created'];

            Cache::forever($cacheKey, $timestamp);

            if (!$this->isRunning) {
                break;
            }

            usleep($this->interval);
        }

        $this->saveGameStatistic();

        return true;
    }

    private function process($timestamp)
    {
        $parameters = [
            'limit'         => static::LIMITATION,
            'sort_by'       => 'created',
            'sort_type'     => 'asc',
            'range' => [
                'field'     => 'created',
                'gte'       => Carbon::parse($timestamp)->addMinute()->format(static::DATE_TIME_PATTERN),
                'lte'       => null
            ]
        ];

        $rawVideos = Elasticsearch::fetchVideos($parameters);
        $videos = $this->toData($rawVideos);

        $this->isRunning = !$videos->isEmpty();

        $this->countGames($videos);

        return $videos->last();
    }

    private function countGames($videos)
    {
        return collect($videos)->groupBy('game_id')
            ->each(function ($value, $key) {
                $this->counter[$key] = [
                    'game_id'       => $key,
                    'total_videos'  => count($value)
                ];
            });
    }

    private function saveGameStatistic()
    {
        $originalGameIds = MasterdataService::getOneTable('games')->pluck('id')->toArray();

        foreach ($originalGameIds as $gameId) {

            $game = GameStatistic::firstOrCreate([
                'game_id' => $gameId
            ]);

            $number = empty($this->counter[$gameId]) ? 0 : array_get($this->counter[$gameId], 'total_videos');
            $game->total_videos = $this->isFistTimeRunning ? $number : BigNumber::new($game->total_videos)->add($number)->toString();
            $game->save();
        }
    }

    private function toData($data)
    {
        return collect($data['hits'])->map(function($record) {
                return $record['_source'];
            })
            ->mapWithKeys(function ($record) {
                $record['game_id'] = empty($record['games_id']) ? 0 : $record['games_id'][0];
                return [ $record['id'] => $record ];
            })
            ->sortBy('created');
    }

    private function getCacheKey ()
    {
        return 'videos_counter';
    }

    private function getCacheData($key)
    {
        return Cache::has($key) ? Cache::get($key) : null;
    }
}
