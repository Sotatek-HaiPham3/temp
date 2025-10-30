<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Exception;
use App\Exceptions\Reports\InvalidRequestException;
use Cache;
use App\Traits\VideoTrait;

class ContentApplication {

    use VideoTrait;

    const CONTENT_TYPE_UPLOAD_ID = 2;

    const CACHE_TIME_LIVE = 600; // 10 minutes

    private $builder;

    private $prefixApi = 'api/';

    public function __construct()
    {
        $this->builder = $this->getBuilder();
    }

    public function getContents($params = [])
    {
        $forceFetch     = array_get($params, 'force_fetch', Consts::FALSE);
        $page           = array_get($params, 'page', 1);
        $limit          = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);

        $cacheKey = $this->getCacheKey($page, $limit, $params);

        if (Cache::has($cacheKey) && !$forceFetch) {
            return Cache::get($cacheKey);
        }

        $contents = $this->builder->table('Content')
            ->where('content_type_id', static::CONTENT_TYPE_UPLOAD_ID)
            ->where('discoverable', Consts::TRUE)
            ->where('transcoded', Consts::TRUE)
            ->when(!empty($params['video_ids']), function ($query) use ($params) {
                $query->whereIn('id', $params['video_ids']);
            })
            ->when(!empty($params['user_id']), function ($query) use ($params) {
                // $query->where('user_id', $params['user_id']);
                $query->where('thumbnail', 'like', "%/{$params['user_id']}/%");
            })
            ->when(
                empty($params['is_user_video']),
                function ($query) use ($params) {
                    $query->where('feature', Consts::TRUE);
                },
                function ($query) use ($params) {
                    $query->where('feature', Consts::FALSE);
                }
            )
            // ->when(
            //     !empty($params['sortBy']),
            //     function ($query) use ($params) {
            //         $query->orderBy($params['sortBy'], 'desc');
            //     },
            //     function ($query) {
            //         $query->orderBy('last_modified', 'desc');
            //     }
            // )
            ->orderBy('last_modified', 'desc')
            ->paginate($limit);

        $userIds = $contents->pluck('user_id')->toArray();
        $mapUsers = $this->getUsersInfo($userIds);

        $contents->getCollection()->transform(function ($item) use ($mapUsers) {
            return $this->normalizeItem($item, $mapUsers);
        });

        $result = $contents->toArray();
        Cache::put($cacheKey, $result, $this->getCacheTimeLive());

        return $result;
    }

    private function normalizeItem($item, $mapUsers)
    {
        $user = $this->modifyUserInfo($item, $mapUsers);

        return [
            'id'                => $item->id,
            'user'              => $user,
            'game'              => $this->getGameInfo($item->games_id),
            'games_id'          => $item->games_id,
            'title'             => property_exists($item, 'title') ? $item->title : null,
            'description'       => $item->description,
            'notes'             => $item->notes,
            'mimetype'          => $item->mimetype,
            'thumbnail'         => $item->thumbnail ? $this->getFullPath($item->thumbnail) : null,
            'video_path'        => $item->video ? $this->getFullPath($item->video) : null,
            'total_views'       => floor(rand() * 100),
            'total_comments'    => floor(rand() * 100),
            'total_likes'       => floor(rand() * 100),
            'created'           => $item->created,
            'is_finished'       => $item->transcoded && $item->discoverable
        ];
    }

    private function getCacheKey($page, $limit, $params = [])
    {
        $params[] = $page;
        $params[] = $limit;

        return sprintf('%s.%s', 'content.app', gamelancer_hash(json_encode($params)));
    }

    private function getBuilder()
    {
        return DB::connection('content');
    }

    public function triggerTranscodingVideo($data)
    {
        $api = '/users/verifyUpload';

        $fullUrl = sprintf('%s/%s/%s', Utils::trimChar($this->getEndPoint()), Utils::trimChar($this->prefixApi),
            Utils::trimChar($api));


        $domain = env('SESSION_DOMAIN', '.gamelancer.com');
        $cookieJar = CookieJar::fromArray($this->getCookies(), $domain);

        $client = new Client();
        $response = $client->request('POST', $fullUrl, [
            'form_params'   => $data,
            'cookies'       => $cookieJar,
        ]);

        return $this->handleResponse($response);
    }

    private function getCookies()
    {
        $strCookie = request()->header('cookie');
        $data = [];
        foreach (explode('; ', $strCookie) as $cookie) {
            list($key, $value) = explode('=', $cookie);
            $data[$key] = $value;
        }

        return $data;
    }

    private function getEndPoint()
    {
        return env('CONTENT_ENDPOINT', 'http://localhost');
    }

    private function handleResponse($response)
    {
        $data = json_decode($response->getBody(), true);

        if ($response->getStatusCode() !== 200 || empty($data['success'])) {
            throw new InvalidRequestException('content.verify_upload');
        }

        return $data;
    }

    public function getVideoInfo($videoId)
    {
        $video = $this->builder->table('Content')
            ->where('id', $videoId)
            ->first();

        if (!$video) {
            return null;
        }

        $mapUsers = $this->getUsersInfo([$video->user_id]);
        return $this->normalizeItem($video, $mapUsers);
    }

}
