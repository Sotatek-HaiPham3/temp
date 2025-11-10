<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Exception;
use ContentApp;
use Elasticsearch;
use App\Utils;
use App\Consts;
use Auth;
use DB;
use App\Http\Services\MasterdataService;
use App\Utils\NodebbUtils;
use App\Events\VideoUpdated;
use App\Traits\NotificationTrait;
use App\Models\UserVideoTopic;
use App\Models\TopicStatistic;

class MediaAPIController extends AppBaseController
{
    use NotificationTrait;

    /**
    * @SWG\Get(
    *   path="/medias/videos",
    *   summary="Get Videos",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="slug", in="query", required=false, type="string"),
    *   @SWG\Parameter(name="video_id", in="query", required=false, type="integer"),
    *   @SWG\Parameter(name="sortBy", in="query", required=false, type="string", enum={"newest", "views"}),
    *   @SWG\Parameter(name="views", in="query", required=false, type="string", description="Ex:1_500"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getVideos(Request $request)
    {
        $params = $request->all();

        $params = $this->appendParameter($params);

        if ($request->has('user_id')) {
            $params['user_id'] = Auth::id();
        }

        $data = $this->pullVideos($params);
        $data = $this->appendVideoComments($data);

        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/medias/videos/info",
    *   summary="Get Video Info",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="id", in="query", required=true, type="number"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getVideoInfoById(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $videoId = $request->id;
            $data = $this->pullVideoInfo($videoId) ?: [];
            // $data = $this->appendTopicForVideo($data);

            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/medias/videos/topic-info",
    *   summary="Get Topic For Video",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="id", in="query", required=true, type="number"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getTopicForVideoByVideoId(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $videoId = $request->id;
            $data = $this->pullVideoInfo($videoId) ?: [];
            $data = $this->appendTopicForVideo($data);

            DB::commit();
            return $this->sendResponse(array_get($data, 'topic'));
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/medias/user/videos",
    *   summary="Get User Videos",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="user_id", in="query", required=true, type="number"),
    *   @SWG\Parameter(name="slug", in="query", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUserVideos(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $params = $request->all();

        $params['is_user_video']    = Consts::TRUE;
        $params['force_fetch']      = Consts::TRUE;

        if ($request->has('slug')) {
            $params = $this->appendParameter($params);
        }

        $data = $this->pullVideos($params);
        $data = $this->appendVideoComments($data);

        return $this->sendResponse($data);
    }


    /**
    * @SWG\Get(
    *   path="/medias/videos/featured",
    *   summary="Get Featured Videos",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="slug", in="query", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getFeaturedVideos(Request $request)
    {
        $params = $request->all();

        if ($request->has('slug')) {
            $params = $this->appendParameter($params);
        }

        $data = $this->pullFeaturedVideos($params);
        $data = $this->appendVideoComments($data);

        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/medias/videos/recently-added",
    *   summary="Get Recently Added Videos",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="slug", in="query", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getRecentlyAddedVideos(Request $request)
    {
        $params = $request->all();

        $params['sortBy']       = Consts::VIDEO_SORT_BY_NEWEST;
        $params['force_fetch']  = Consts::TRUE;

        if ($request->has('slug')) {
            $params = $this->appendParameter($params);
        }

        $data = $this->pullVideos($params);
        $data = $this->appendVideoComments($data);

        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/medias/videos/suggest",
    *   summary="Get Suggestion Videos",
    *   tags={"Medias"},
    *   security={
    *   },
    *   @SWG\Parameter(name="id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getSuggestionVideos(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $params = $request->all();

        $params['sortBy']       = Consts::VIDEO_SORT_BY_NEWEST;
        $params['force_fetch']  = Consts::TRUE;

        $data = $this->pullSuggestionVideos($params);
        $data = $this->appendVideoComments($data);

        return $this->sendResponse($data);
    }

    public function listenVideoTranscodingWebhook(Request $request)
    {
        if (!$request->has('content')) {
            return;
        }

        $info = json_decode(base64_decode($request->content));

        $data = [
            'video_id'      => $info->content_id,
            'id'            => $info->content_id,
            'video_path'    => $info->video_path,
            'thumbnail'     => $info->thumbnail,
            'mimetype'      => $info->mimetype
        ];
        event(new VideoUpdated($data));

        $videoInfo = $this->pullVideoInfo(
            $info->content_id,
            ['force_fetch' => Consts::TRUE]
        );

        $notificationParams = [
            'user_id' => !empty($videoInfo['user']) ? $videoInfo['user']['user_id'] : null,
            'type' => Consts::NOTIFY_TYPE_VIDEO_ONLINE,
            'message' => Consts::NOTIFY_VIDEO_UPLOAD,
            'props' => ['video' => array_get($videoInfo, 'title')],
            'data' => ['video_id' => $info->content_id]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_VIDEO, $notificationParams);
    }

    private function pullVideos($params)
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getVideos($params);
        }

        return ContentApp::getContents($params);
    }

    private function pullFeaturedVideos($params)
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getFeaturedVideos($params);
        }

        return ContentApp::getContents($params);
    }

    private function pullSuggestionVideos($params)
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getSuggestionVideos($params);
        }

        return ContentApp::getContents($params);
    }

    private function pullVideoInfo($videoId, $params = [])
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getVideoInfo($videoId, $params);
        }

        return ContentApp::getVideoInfo($videoId);
    }

    private function appendParameter($params)
    {
        $slug               = array_get($params, 'slug', null);
        $similarVideoId     = array_get($params, 'video_id', null);

        // by game slug
        $game = MasterdataService::getOneTable('games')
            ->where('slug', $slug)
            ->first();

        // no exists game and try getting by similar video id
        if (!$game && $similarVideoId) {
            $videoInfo = $this->pullVideoInfo($similarVideoId);
            $game = MasterdataService::getOneTable('games')
                ->where('id', $videoInfo['games_id'])
                ->first();
        }

        if ($game) {
            $params['game_ids'] = [$game->id];
        }

        return $params;
    }

    private function buildTopicForVideo($data)
    {
        $videoIds = collect($data['data'])->pluck('id');
        $videoIdsExistsTopic = UserVideoTopic::whereIn('video_id', $videoIds)->pluck('video_id')->all();

        $data['data'] = collect($data['data'])->transform(function ($video) use ($videoIdsExistsTopic) {
            if (!empty($video['id']) && in_array($video['id'], $videoIdsExistsTopic)) {
                $video = $this->appendTopicForVideo($video);
            }

            return $video;
        });

        return $data;
    }

    private function appendTopicForVideo($video)
    {
        $hasTopic = array_key_exists('user', $video)
                && array_key_exists('username', $video['user']);
                // && !$request->has('no_topic');

        if ($hasTopic && !empty($video['is_finished'])) {
            $video = NodebbUtils::buildTopicForVideo((object) $video);
        }

        return $video;
    }

    private function appendVideoComments($data)
    {
        $videoIds = collect($data['data'])->pluck('id');

        $statistic = TopicStatistic::join('user_video_topics', function ($join) {
                return $join->on('user_video_topics.user_id', '=', 'topic_statistics.user_id')
                    ->on('user_video_topics.topic_id', '=', 'topic_statistics.topic_id');
            })
            ->whereIn('user_video_topics.video_id', $videoIds)
            ->select('topic_statistics.*', 'user_video_topics.video_id')
            ->get()
            ->mapWithKeys(function ($record) {
                return [ $record['video_id'] => $record ];
            });

        $data['data'] = collect($data['data'])->transform(function ($video) use ($statistic) {
            $video['total_comments'] = empty($statistic[$video['id']]) ? 0 : $statistic[$video['id']]->total_comments;
            return $video;
        });

        return $data;
    }
}
