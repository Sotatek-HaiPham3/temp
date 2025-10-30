<?php

namespace App\Http\Services;

use App\Consts;
use DB;
use Cache;
use Elasticsearch\ClientBuilder;
use App\Traits\VideoTrait;
use Illuminate\Support\Collection;

class ElasticsearchService {

    use VideoTrait;

    private $client;

    private $index;

    private $must                   = [];

    private $should                 = [];

    private $range                  = [];

    private $sort                   = [];

    private $filter                 = [];

    private $moreLike               = [];

    private $paginate               = [];

    const CACHE_TIME_LIVE           = 600; // 10 minutes

    const CONTENT_INDEX             = 'content'; // content path

    public function __construct()
    {
        $this->client = $this->initClient();
    }

    private function initClient()
    {
        $config = config('aws.app.elasticsearch');

        $hosts = [
            'host'      => $config['host'],
            'port'      => $config['port'],
            'scheme'    => $config['scheme'],
        ];

        return ClientBuilder::create()
            ->setHosts(array($hosts))
            ->setBasicAuthentication($config['username'], $config['password'])
            ->build();
    }

    public function getVideos($params = [])
    {
        $forceFetch     = array_get($params, 'force_fetch', Consts::FALSE);
        $sortBy         = array_get($params, 'sortBy', Consts::VIDEO_SORT_BY_NEWEST);
        $page           = array_get($params, 'page', 1);
        $limit          = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);
        $isUserVideo    = array_get($params, 'is_user_video', null);
        $views          = array_get($params, 'views', null);

        $cacheKey = $this->getkey($params, $page, $limit);

        if (!$isUserVideo && $views) {
            list($gteViewcount, $lteViewcount) = explode(Consts::CHAR_UNDERSCORE, $views);
            $this->range('viewcount', $gteViewcount, $lteViewcount);
        }

        if (Cache::has($cacheKey) && !$forceFetch) {
            return Cache::get($cacheKey);
        }

        list($totalRecord, $videos) = $this->getVideosByType($params);

        $data = [
            'data'          => $videos,
            'current_page'  => intval($page),
            'per_page'      => intval($limit),
            'last_page'     => intval(ceil($totalRecord / $limit)),
            'total'         => $totalRecord,
        ];

        Cache::put($cacheKey, $data, $this->getCacheTimeLive());

        return $data;
    }

    private function getVideosByType($params = [])
    {
        $videos = [];
        $totalRecord = 0;
        $mapViewsCounter = [];

        $sortBy = array_get($params, 'sortBy', Consts::VIDEO_SORT_BY_NEWEST);

        switch ($sortBy) {
            case Consts::VIDEO_SORT_BY_VIEWS:
                $params = array_merge($params, [
                    'sort_by'   => 'viewcount',
                    'sort_type' => 'desc'
                ]);
                break;
            case Consts::VIDEO_SORT_BY_NEWEST:
            default:
                $params = array_merge($params, [
                    'sort_by'   => 'id',
                    'sort_type' => 'desc'
                ]);
                break;
        }

        $rawData = $this->fetchVideos($params);
        $totalRecord = $rawData['total']['value'];
        $videos = $this->toDataWithKeys($rawData['hits']);

        $videos = $this->normalizeVideos($videos);

        return [ $totalRecord, $videos ];
    }

    public function getFeaturedVideos($params)
    {
        $page       = 1;
        $limit      = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);
        $games_id   = array_get($params, 'games_id', []);

        $cacheKey = $this->getkey($params, $page, $limit, 'elasticsearch.videos.featured_%s');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $data = [
            'page'      => $page,
            'limit'     => $limit
        ];

        list($totalRecordNewest, $newestVideos) = $this->getVideosByType(
            array_merge($params, $data, [
                'sortBy' => Consts::VIDEO_SORT_BY_NEWEST
            ])
        );

        list($totalRecordViews, $viewsVideos) = $this->getVideosByType(
            array_merge($params, $data, [
                'sortBy' => Consts::VIDEO_SORT_BY_VIEWS
            ])
        );

        $result = collect($newestVideos)
            ->concat($viewsVideos)
            ->unique('id')
            ->shuffle()
            ->toArray();


        $totalRecord = count($result);
        $data = [
            'data'          => $result,
            'current_page'  => intval($page),
            'per_page'      => intval($limit),
            'last_page'     => intval(ceil($totalRecord / $limit)),
            'total'         => $totalRecord
        ];

        Cache::put($cacheKey, $data, $this->getCacheTimeLive());

        return $data;
    }

    public function fetchVideos($params = [])
    {
        $page           = array_get($params, 'page', 1);
        $limit          = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);

        $videoId        = array_get($params, 'video_id', null);
        $videoIds       = array_get($params, 'video_ids', null);
        $gameIds        = array_get($params, 'game_ids', []);
        $tags           = array_get($params, 'tags', []);
        $userId         = array_get($params, 'user_id', null);

        $isUserVideo    = array_get($params, 'is_user_video', null);
        $isSuggested    = array_get($params, 'is_suggested', null);
        $isVideoDefail  = array_get($params, 'is_video_detail', null); // search video by video_id

        $sortBy         = array_get($params, 'sort_by', 'id');
        $sortType       = array_get($params, 'sort_type', 'desc');

        $this->index = static::CONTENT_INDEX;
        $this->must('transcoded', Consts::TRUE);

        if (!$isVideoDefail && !$isUserVideo) {
            $this->must('feature', Consts::TRUE);
        }

        if (!empty($videoIds)) {
            $this->must('id', $videoIds);
        }

        if (!empty($userId)) {
            $this->must('user_id', $userId);
        }

        if (!empty($gameIds)) {
            $this->must('games_id', $gameIds);
        }

        if (!empty($tags)) {
            $this->must('tags', $tags);
        }

        if (!empty($params['range'])) {
            $range = $params['range'];
            $this->range($range['field'], $range['gte'], $range['lte']);
        }

        // For suggestion videos.
        if (!empty($isSuggested) && !empty($videoId)) {
            $this->suggest($videoId);
        }

        $this->sort($sortBy, $sortType);

        $this->filter('discoverable', Consts::TRUE);
        $this->paginate(
            ($page - 1) * $limit,
            $limit
        );

        $data = $this->buildQueryAndExecute()['hits'];
        return $data;
    }

    private function normalizeVideos($data)
    {
        $userIds = collect($data)->pluck('user_id')->toArray();
        $mapUsers = $this->getUsersInfo($userIds);

        $result = [];
        collect($data)->map(function ($item, $key) use ($mapUsers, &$result) {
                $record = (object) $item;

                $user = $this->modifyUserInfo($record, $mapUsers);

                $tags = property_exists($record, 'tags_title') && !empty($record->tags_title)
                    ? $record->tags_title
                    : [];

                $gameId = is_array($record->games_id) ? collect($record->games_id)->first() : $record->games_id;

                $result[] = [
                    'id'                => intval($record->id),
                    'user'              => $user,
                    'game'              => $this->getGameInfo($gameId),
                    'games_id'          => intval($gameId),
                    'title'             => property_exists($record, 'title') ? $record->title : null,
                    'tags'              => $tags,
                    'description'       => $record->description,
                    'mimetype'          => $record->mimetype,
                    'thumbnail'         => $record->thumbnail ? $this->getFullPath($record->thumbnail) : null,
                    'thumbnails'        => property_exists($record, 'thumbnails') ? $this->toThumbnails($record->thumbnails) : [],
                    'video_path'        => $record->video ? $this->getFullPath($record->video) : null,
                    'total_views'       => property_exists($record, 'viewcount') ? $record->viewcount : 0,
                    'total_comments'    => 0,
                    'total_likes'       => 0,
                    'created'           => $record->created,
                    'is_finished'       => $record->transcoded && $record->discoverable
                ];
            });

        return $result;
    }

    private function toThumbnails($data = [])
    {
        return collect($data)->map(function ($record) {
            $record['thumbnail'] = $this->getFullPath($record['s3key']);
            unset($record['s3key']);
            return $record;
        })->toArray();
    }

    private function getkey($params, $page, $limit, $prefixKey = null)
    {
        $key = $this->buildKey($page, $limit, $params);

        if (!$prefixKey) {
            $prefixKey = 'elasticsearch.videos_%s';
        }

        return sprintf($prefixKey, $key);
    }

    private function buildKey($page, $limit, $params = [])
    {
        $params[] = $page;
        $params[] = $limit;

        return gamelancer_hash(json_encode($params));
    }

    public function getVideoInfo($videoId, $params = [])
    {
        $forceFetch = array_get($params, 'force_fetch', Consts::FALSE);

        $cacheKey   = sprintf('elasticsearch.video_%s', $videoId);
        if (Cache::has($cacheKey) && !$forceFetch) {
            return Cache::get($cacheKey);
        }

        $data = [
            'page'                  => 1,
            'limit'                 => 1,
            'is_video_detail'       => Consts::TRUE,
            'video_ids'             => [$videoId]
        ];

        $rawData = $this->fetchVideos($data);
        $videos = $this->toDataWithKeys($rawData['hits']);

        $videos = $this->normalizeVideos($videos);

        $info = collect($videos)->first();

        Cache::put($cacheKey, $info, $this->getCacheTimeLive());

        return $info;
    }

    public function getSuggestionVideos($params)
    {
        $page           = array_get($params, 'page', 1);
        $limit          = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);
        $videoId        = array_get($params, 'id');

        $data = array_merge($params, [
            'page'          => $page,
            'limit'         => $limit,
            'is_suggested'  => Consts::TRUE,
            'video_id'      => $videoId,
        ]);

        return $this->getVideos($data);
    }

    private function buildQueryAndExecute()
    {
        $body = [
            'sort'  => $this->sort
        ];

        if (!empty($this->paginate['from'])) {
            $body['from']  = $this->paginate['from'];
        }

        if (!empty($this->paginate['limit'])) {
            $body['size']  = $this->paginate['limit'];
        }

        $query = $this->buildQuery();
        if (!empty($query)) {
            $body['query'] = $query;
        }

        logger()->info('============Elasticsearch Parameters = ', [$body]);

        $response = $this->client->search([
            'index' => $this->index,
            'body'  => $body
        ]);

        $this->reset();

        return $response;
    }

    private function buildQuery()
    {
        $query = [];

        if (!empty($this->must)) {
            $query['bool']['must'] = $this->must;
        }

        if (!empty($this->filter)) {
            $query['bool']['filter'] = $this->filter;
        }

        if (!empty($this->should)) {
            $query['bool']['should'] = $this->should;
        }

        if (!empty($this->range)) {
            $query['bool']['must'][] = [
                'range' => $this->range
            ];
        }

        if (!empty($this->moreLikeThis)) {
            $query['bool']['must'][] = [
                'more_like_this' => $this->moreLikeThis
            ];
        }

        return $query;
    }

    private function must($key, $value)
    {
        if (!$this->isValid($value)) {
            return;
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $this->must[]['terms'][$key] = $value;
            return;
        }

        $this->must[]['term'][$key] = $value;
    }

    private function should($key, $value)
    {
        if (!$this->isValid($value)) {
            return;
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $this->should[]['terms'][$key] = $value;
            return;
        }

        $this->should[]['term'][$key] = $value;
    }

    private function sort($key='score', $value='desc')
    {
        $this->sort[][$key] = [
            'order' => $value
        ];
    }

    private function filter($key, $value)
    {
        if ($this->isValid($value)) {
            $this->filter[]['term'][$key] = $value;
        }
    }

    private function range($key, $gte = 0, $lte = null)
    {
        $gte = $gte ? $gte : 0;

        $range = $lte
            ? [ 'gte' => $gte, 'lte' => $lte ]
            : [ 'gte' => $gte ];

        $this->range[$key] = $range;
    }

    private function suggest($videoId)
    {
        $this->moreLikeThis = [
            'fields'            => ['title', 'description'],
            'like'              => [
                '_index'    => static::CONTENT_INDEX,
                '_id'       => $videoId
            ],
            'min_term_freq'     => 1,
            'min_doc_freq'      => 1,
            'max_query_terms'   => 12
        ];
    }

    private function paginate($from = 0, $limit = Consts::DEFAULT_PER_PAGE)
    {
        $this->paginate = [
            'from'  => $from,
            'limit'  => $limit
        ];
    }

    private function reset()
    {
        $this->index            = null;
        $this->must             = [];
        $this->filter           = [];
        $this->should           = [];
        $this->sort             = [];
        $this->range            = [];
        $this->paginate         = [];
        $this->moreLikeThis     = [];
    }

    private function isValid($value)
    {
        if (is_array($value)) {
            return !empty($value);
        }

        return !is_null($value) && strlen($value);
    }

    private function isValidNumeric($value)
    {
        return !is_null($value) && is_numeric($value);
    }

    private function toDataWithKeys($data)
    {
        return collect($data)->map(function($record) {
                return $record['_source'];
            })
            ->mapWithKeys(function ($record) {
                return [ $record['id'] => $record ];
            });
    }
}
