<?php

namespace App\Utils;

use App\Models\User;
use App\Models\UserVideoTopic;
use Nodebb;
use Cache;
use Auth;
use Elasticsearch;
use ContentApp;
use App\Consts;
use App\Utils;
use App\Traits\RedisTrait;
use Illuminate\Support\Facades\DB;
use App\Events\TopicCreated;
use Exception;

class NodebbUtils {

    use RedisTrait;

    const DEAULT_PER_PAGE_FOR_TOPIC = 5;

    const DEAULT_PER_PAGE_FOR_POST = 5;

    const DEAULT_PER_PAGE_FOR_SUB_POST = 3;

    const CACHE_TIME_LIVE = 600; // 10 minutes

    public static function getTopicsForUser($params)
    {
        $data = static::getTopicsFromCacheByPage($params);
        if (empty($data)) {
            $data = static::getMoreTopicsFromNodebb($params);
        }

        return $data;
    }

    private static function getTopicsFromCacheByPage($params)
    {
        $page = array_get($params, 'page', 1);
        $username = array_get($params, 'username');
        $userId = User::where('username', $username)->value('id');

        $userTopics = static::getUserTopicsFromCache();
        $topicsFromCache = static::getTopicsFromCache();

        $topicIds = !empty($userTopics[$userId]) ? $userTopics[$userId] : [];
        $topics = $topicsFromCache->whereIn('tid', $topicIds);
        if (empty($topicIds) || !$topics->isNotEmpty()) {
            return [];
        }

        $paginator = Utils::convertArrayToPagination($topicIds, static::DEAULT_PER_PAGE_FOR_TOPIC)->toArray();

        $topicIds = static::getDataByPage($topicIds, $page, static::DEAULT_PER_PAGE_FOR_TOPIC);
        if (empty($topicIds)) {
            return [];
        }

        $topics = $topicsFromCache->whereIn('tid', $topicIds)->sortByDesc('timestamp');
        $paginator['data'] = static::modifyTopic($topics);

        return $paginator;
    }

    private static function getMoreTopicsFromNodebb($params)
    {
        $username = array_get($params, 'username');
        $topics = Nodebb::getTopicsForUser($username, ['page' => array_get($params, 'page', 1)]);

        $topics = static::saveMoreTopicsToCache($username, $topics->topics);

        return static::getTopicsFromCacheByPage($params);
    }

    private static function modifyTopic($topics)
    {
        $userActions = static::getUserActionsFromCache();

        $topics->transform(function ($topic) use ($userActions) {
            $videoId = $videoId ?? UserVideoTopic::checkExistsVideoForTopic($topic->tid);
            if ($videoId) {
                $topic->video = static::pullVideoInfo($videoId);
            }

            $posts = $topic->posts;

            $paginator = Utils::convertArrayToPagination($posts, static::DEAULT_PER_PAGE_FOR_POST)->toArray();

            $posts = static::getDataByPage($posts, 1);
            $paginator['current_page'] = 1;
            $paginator['data'] = static::modifyPosts($topic, $posts);
            $topic->posts = $paginator;

            $mainPost = static::modifyPosts($topic, [$topic->mainPost]);
            $topic->mainPost = collect($mainPost)->first();

            return $topic;
        });

        return $topics;
    }

    private static function modifyPosts($topic, $posts)
    {
        collect($posts)->transform(function ($post) use ($topic) {
            $post = static::modifyActions($topic, $post);

            $post = static::modifySubPosts($topic, $post);

            return $post;
        });

        return $posts;
    }

    private static function modifyActions($topic, $post)
    {
        $userId = static::getUserIdLogged();
        if (!$userId) {
            $post->upvoted = false;
            $post->downvoted = false;
            return $post;
        }

        $tid = $post->tid;
        $pid = $post->pid;

        $userActions = static::getUserActionsFromCache();
        if (empty($userActions["{$userId}_{$tid}"][$pid])) {
            // $userActions = static::getUserActionsFromNodebb($topic);
            return $post;
        }

        $userAction = $userActions["{$userId}_{$tid}"][$pid];

        $post->votes = $userAction->votes;
        $post->upvotes = $userAction->upvotes;
        $post->upvoted = $userAction->upvoted;
        $post->downvotes = $userAction->downvotes;
        $post->downvoted = $userAction->downvoted;

        return $post;
    }

    public static function modifySubPosts($topic, $post)
    {
        if (!property_exists($post, 'subPosts')) {
            $paginator = Utils::convertArrayToPagination([], static::DEAULT_PER_PAGE_FOR_SUB_POST)->toArray();
            $post->subPosts = $paginator;

            return $post;
        }

        $subPosts = $post->subPosts;
        $paginator = Utils::convertArrayToPagination($subPosts, static::DEAULT_PER_PAGE_FOR_SUB_POST)->toArray();
        $subPosts = static::getDataByPage($subPosts, 1, static::DEAULT_PER_PAGE_FOR_SUB_POST);
        $paginator['current_page'] = 1;
        $paginator['data'] = static::modifyPosts($topic, $subPosts);
        $post->subPosts = $paginator;

        return $post;
    }

    private static function getUserActionsFromNodebb($topic)
    {
        $posts = Nodebb::getPostsForTopic($topic->user->username, $topic->slug);
        static::saveUserActionsToCache($topic->tid, $posts->posts);

        return static::getUserActionsFromCache();
    }

    private static function saveUserTopicsToCache($userId, $topicIds)
    {
        $userTopics = static::getUserTopicsFromCache();

        $userTopics[$userId] = !empty($userTopics[$userId]) ? $userTopics[$userId] : collect([]);
        $userTopics[$userId] = $userTopics[$userId]->concat($topicIds)->unique()->sort(function($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? 1 : -1;
        })->mapWithKeys(function ($item) {
            return [$item => $item];
        });

        static::saveAndSetExpireDataToCache(static::getUserTopicsKey(), $userTopics);
    }

    private static function getUserTopicsFromCache()
    {
        $key = static::getUserTopicsKey();
        return static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);
    }

    public static function buildTopicForVideo($video)
    {
        try {
            $topic = static::createTopicForVideoIfNeed($video);
            $video->topic = $topic;
        } catch (Exception $e) {
            logger()->error('=====buildTopicForVideo=====: ', [$e]);
        }

        return (array) $video;
    }

    private static function createTopicForVideoIfNeed($video)
    {
        $user = (object) $video->user;
        $topicId = UserVideoTopic::checkExistsTopicForVideo($video->id);

        $topic = static::getTopicById($topicId, $user->username);
        if (!$topic) {
            $topic = static::createTopicForVideo($video, $user);
        }

        return static::modifyTopic(collect([$topic]))->first();
    }

    public static function createTopicForVideo($video, $user)
    {
        $data = [
            'content' => static::getContentForVideo($video)
        ];
        $topic = Nodebb::createTopicForVideo($data, $user->username);
        $topic = static::buildTopics([
            'topics' => [$topic->payload->topicData],
            'videoId' => $video->id,
            'username' => $user->username
        ]);

        $topic = collect($topic)->first();
        UserVideoTopic::create([
            'user_id' => $user->user_id,
            'video_id' => $video->id,
            'topic_id' => $topic->tid
        ]);

        event(new TopicCreated(['topic' => [$topic]]));

        return $topic;
    }

    private static function getContentForVideo($video)
    {
        if (!empty($video->description)) {
            return $video->description;
        }

        $game = property_exists($video, 'game') ? $video->game : null;
        if (!empty($game) && !empty($game['title'])) {
            return $game['title'];
        }

        return 'N/A';
    }

    public static function createTopic($data)
    {
        $topic = Nodebb::createTopic($data);
        $topic = static::buildTopics([
            'topics' => [$topic->payload->topicData],
            'username' => Auth::user()->username
        ]);

        return static::modifyTopic($topic);
    }

    public static function saveMoreTopicsToCache($username, $topics)
    {
        if (empty($topics)) {
            return;
        }

        $topics = collect($topics)->mapWithKeys(function ($item) {
            return [$item->tid => (object) $item];
        });

        $user = Auth::guard('api')->user();
        return static::buildTopics([
            'topics' => $topics,
            'username' => $user ? $user->username : $username
        ]);
    }

    private static function buildTopics($params)
    {
        $topics = array_get($params, 'topics');
        $username = array_get($params, 'username');
        $videoId = array_get($params, 'videoId');

        $videos = static::getVideoForTopics($topics);

        $data = [];
        collect($topics)->each(function ($topic) use (&$data, $username, $videos) {
            $user = static::getUserInfoByUid($topic->uid);
            $topic->user = $user;

            $username = $username ?? strtolower($user->username);
            $posts = Nodebb::getPostsForTopic($username, $topic->slug);

            static::saveUserActionsToCache($topic->tid, $posts->posts);

            $topic->mainPost = static::getMainPostForTopic($posts);

            $posts = static::buildPosts($posts);
            $topic->posts = $posts;

            $topic->video = collect($videos)->firstWhere('tid', $topic->tid);

            $data[] = static::normalizeTopic($topic);
        });

        return static::saveTopicsToCache($data);
    }

    private static function normalizeTopic($topic)
    {
        return (object) [
            'tid'             => $topic->tid,
            'uid'             => $topic->uid,
            'cid'             => $topic->cid,
            'slug'            => $topic->slug,
            'mainPid'         => $topic->mainPid,
            'votes'           => $topic->votes,
            'upvotes'         => $topic->upvotes,
            'downvotes'       => $topic->downvotes,
            'viewcount'       => $topic->viewcount,
            'user'            => $topic->user,
            'posts'           => $topic->posts,
            'postcount'       => $topic->postcount - 1,
            'mainPost'        => $topic->mainPost,
            'video'           => $topic->video,
            'deleted'         => $topic->deleted,
            'timestamp'       => $topic->timestamp
        ];
    }

    private static function getVideoForTopics($topics)
    {
        $topicIds = collect($topics)->pluck('tid');

        $videoTopics = UserVideoTopic::select('video_id', 'topic_id')
            ->whereIn('topic_id', $topicIds)
            ->get();

        if (!$videoTopics->count()) {
            return [];
        }

        $data = static::pullVideos([
            'video_ids' => $videoTopics->pluck('video_id')->toArray(),
            'limit' => $videoTopics->count()
        ]);

        $videos = [];
        collect($data['data'])->each(function ($video) use (&$videos, $videoTopics) {
            $video['tid'] = $videoTopics->firstWhere('video_id', $video['id'])->topic_id;

            $videos[] = $video;
        });

        return $videos;
    }

    private static function saveTopicsToCache($topics)
    {
        $topics = collect($topics)->mapWithKeys(function ($item) {
            return [$item->tid => $item];
        })->filter();

        $topicsFromCache = static::getTopicsFromCache();
        $data = $topicsFromCache->concat($topics)->mapWithKeys(function ($item) {
            return [$item->tid => $item];
        })
        ->unique('tid')
        ->sortByDesc('timestamp');

        static::saveAndSetExpireDataToCache(static::getTopicsKey(), $data);

        $topicIds = $topics->pluck('tid');
        $userId = $topics->first()->user->id;
        static::saveUserTopicsToCache($userId, $topicIds);

        return $topics;
    }

    private static function getTopicsFromCache()
    {
        $key = static::getTopicsKey();
        return static::hasKeyInCache($key) ? collect(static::getFromCache($key)) : collect([]);
    }

    public static function pullVideoInfo($videoId)
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getVideoInfo($videoId);
        }

        return ContentApp::getVideoInfo($videoId);
    }

    private static function pullVideos($params)
    {
        if (Utils::isProduction()) {
            return Elasticsearch::getVideos($params);
        }

        return ContentApp::getContents($params);
    }

    private static function getMainPostForTopic($posts)
    {
        $mainPostId = $posts->mainPid;
        $mainPost = collect($posts->posts)->firstWhere('pid', $mainPostId);

        return $mainPost;
    }

    public static function getPostsForTopic($params)
    {
        $tid = array_get($params, 'tid');
        $page = array_get($params, 'page', 1);
        $username = array_get($params, 'username');

        $data = static::getPostsFormCache($params);
        if (empty($data)) {
            $data = static::getPostsFormNodebb($params);
        }

        return $data;
    }

    private static function getPostsFormCache($params)
    {
        $tid = array_get($params, 'tid');
        $page = array_get($params, 'page', 1);
        $username = array_get($params, 'username');
        $topic = static::getTopicById($tid, $username);
        if (empty($topic)) {
            return [];
        }

        $posts = $topic->posts;
        $paginator = Utils::convertArrayToPagination($posts, static::DEAULT_PER_PAGE_FOR_POST);

        $posts = static::getDataByPage($posts, $page);
        if (empty($posts)) {
            return [];
        }

        $paginator->data = static::modifyPosts($topic, $posts);
        return $paginator;
    }

    private static function getPostsFormNodebb($params)
    {
        $tid = array_get($params, 'tid');
        $page = array_get($params, 'page', 1);
        $username = array_get($params, 'username');

        $posts = Nodebb::getPostsForTopic($username, $tid, $params);
        static::savePostsToCache($tid, $username, $posts->posts);

        return static::getPostsFormCache($params);
    }

    public static function getSubPostsForPost($params)
    {
        $tid = array_get($params, 'tid');
        $pid = array_get($params, 'pid');
        $page = array_get($params, 'page', 1);
        $username = array_get($params, 'username');

        $topic = static::getTopicById($tid, $username);
        if (empty($topic)) {
            return [];
        }

        $post = collect($topic->posts)->where('pid', $pid)->first();
        $subPosts = $post->subPosts;
        $paginator = Utils::convertArrayToPagination($subPosts, static::DEAULT_PER_PAGE_FOR_SUB_POST)->toArray();
        $posts = static::getDataByPage($subPosts, $page);
        $paginator['data'] = static::modifyPosts($topic, $posts);
        return $paginator;
    }

    private static function getDataByPage($data, $page, $limit = self::DEAULT_PER_PAGE_FOR_POST)
    {
        $result = collect($data)->forPage($page, $limit)->all();

        return $result;
    }

    public static function getTopicById($tid, $username)
    {
        if (!$tid) {
            return;
        }

        $topic = static::getTopicFromCacheById($tid);
        if (empty($topic)) {
            $user = Auth::guard('api')->user();
            $username = $user ? $user->username : $username;
            $topic = static::getTopicFromNodebbById($username, $tid);
        }

        return $topic;
    }

    private static function getTopicFromNodebbById($username, $tid)
    {
        $topic = Nodebb::getPostsForTopic($username, $tid);

        $topic = static::buildTopics([
            'topics' => [$topic],
            'username' => $username
        ]);

        return $topic->first();
    }

    public static function saveNewPostToCache($tid, $username, $post)
    {
        $topic = static::getTopicById($tid, $username);
        if (empty($topic)) {
            return;
        }

        static::saveUserActionsToCache($topic->tid, [$post]);

        $toPid = $post->toPid;
        $post->replies = (object) ['count' => 0];
        $mainPid = $topic->mainPid;
        if ($mainPid !== $toPid) {
            $parentPost = collect($topic->posts)->firstWhere('pid', $toPid);
            $parentPost = static::appendSubPosts($parentPost, [$post]);
            $parentPost->replies = (object) $parentPost->replies ?? (object) ['count' => 0];
            $parentPost->replies->count += 1;
            $post = $parentPost;
        }
        $topic->postcount += 1;
        $topic->posts = collect($topic->posts)->concat([$post])->unique('pid');

        static::saveTopicsToCache([$topic]);
    }

    public static function savePostsToCache($tid, $username, $posts)
    {
        $topic = static::getTopicById($tid, $username);
        if (empty($topic)) {
            return;
        }

        $topic->posts = collect($topic->posts)->concat($posts)->unique('pid');
        $topic->posts = static::buildPosts($topic);

        static::saveTopicsToCache([$topic]);
        static::saveUserActionsToCache($topic->tid, $posts);
    }

    private static function buildPosts($posts)
    {
        $mainPostId = $posts->mainPid;
        $posts = collect($posts->posts)->where('pid', '!=', $mainPostId);
        $subPosts = static::getSubPosts($posts, $mainPostId);
        $subPostIds = $subPosts->pluck('pid');

        $data = [];
        $posts->whereNotIn('pid', $subPostIds)->each(function ($post) use (&$data, $subPosts) {
            $subPosts = $subPosts->where('toPid', $post->pid)
                ->where('deleted', Consts::FALSE)
                ->all();

            $post = static::appendSubPosts($post, $subPosts);
            $post = static::modifyPost($post);
            $data[] = $post;
        });

        return $data;
    }

    private static function appendSubPosts($post, $subPosts)
    {
        $existsSubPosts = [];
        if (property_exists($post, 'subPosts')) {
            $existsSubPosts = $post->subPosts;
        }

        $post->subPosts = collect($existsSubPosts)->concat($subPosts)->unique('pid')->sortBy('timestamp');

        return $post;
    }

    private static function getSubPosts($posts, $mainPostId)
    {
        $data = [];
        $posts->each(function ($post) use (&$data, $mainPostId) {
            if (property_exists($post, 'toPid') && $post->toPid === $mainPostId) {
                return;
            }

            $post = static::modifyPost($post);
            $data[] = $post;
        });

        return collect($data);
    }

    public static function modifyPost($post)
    {
        $post->user = static::getUserInfoByUid($post->uid);
        $post = static::normalizePost($post);
        return $post;
    }

    private static function normalizePost($post)
    {
        return (object) [
            'pid'               => $post->pid,
            'uid'               => $post->uid,
            'tid'               => $post->tid,
            'toPid'             => $post->toPid,
            'user'              => $post->user,
            'content'           => $post->content,
            'topic'             => property_exists($post, 'topic') ? $post->topic : null,
            'replies'           => property_exists($post, 'replies') ? $post->replies : (object) ['count' => 0],
            'subPosts'          => property_exists($post, 'subPosts') ? $post->subPosts : null,
            'timestamp'         => $post->timestamp,
            'downvoted'         => false,
            'downvotes'         => property_exists($post, 'downvotes') ? $post->downvotes : 0,
            'upvoted'           => false,
            'upvotes'           => property_exists($post, 'upvotes') ? $post->upvotes : 0,
            'votes'             => $post->votes,
            'deleted'           => property_exists($post, 'deleted') ? $post->deleted : 0,
        ];
    }

    private static function saveUserActionsToCache($tid, $posts)
    {
        $userId = static::getUserIdLogged();
        if (!$userId) {
            return;
        }

        $userActions = static::getUserActionsFromCache();
        $data = $userActions->toArray();
        collect($posts)->each(function ($post) use (&$data, $userId, $tid) {
            $pid = $post->pid;

            $post = (array) $post;
            $data["{$userId}_{$tid}"][$pid] = (object) [
                'votes'       => array_get($post, 'votes', 0),
                'upvotes'     => array_get($post, 'upvotes', 0),
                'upvoted'     => array_get($post, 'upvoted', false),
                'downvotes'   => array_get($post, 'downvotes', 0),
                'downvoted'   => array_get($post, 'downvoted', false),
            ];
        });
        $userActions = $data;

        static::saveAndSetExpireDataToCache(static::getUserActionsKey(), $userActions);
    }

    public static function updateUserActionsToCache($data)
    {
        if (!property_exists($data, 'post')) {
            return;
        }

        $post = $data->post;
        $upvoted = $data->upvote;
        $downvoted = $data->downvote;
        $userId = static::getUserIdLogged();

        $userActions = static::getUserActionsFromCache();
        $userActions = static::saveMoreUserActionsIfNeed($userActions, $data, $userId);
        $userActions->transform(function ($userAction, $key) use ($post, $userId, $upvoted, $downvoted) {
            $pid = $post->pid;
            $tid = $post->tid;

            list($currentUserId, $currentTid) = explode(Consts::CHAR_UNDERSCORE, $key);
            if ((int) $currentTid !== (int) $tid) {
                return $userAction;
            }

            if (empty($userAction[$pid])) {
                $userAction[$pid] = static::normalizeUserAction($post, $upvoted, $downvoted);

                return $userAction;
            }

            $isCurrentUserLogged = $key === "{$userId}_{$tid}";

            $currentUserAction = clone $userAction[$pid];
            $upvoted = $isCurrentUserLogged ? $upvoted : $currentUserAction->upvoted;
            $downvoted = $isCurrentUserLogged ? $downvoted : $currentUserAction->downvoted;
            $userAction[$pid] = static::normalizeUserAction($post, $upvoted, $downvoted);

            return $userAction;
        });

        static::saveAndSetExpireDataToCache(static::getUserActionsKey(), $userActions);
        static::updateVotesForTopicOrPostToCache($post);
    }

    private static function saveMoreUserActionsIfNeed($userActions, $data, $userId)
    {
        $post = $data->post;
        $upvoted = $data->upvote;
        $downvoted = $data->downvote;
        $pid = $post->pid;
        $tid = $post->tid;

        $key = "{$userId}_{$tid}";
        $keys = $userActions->keys();

        if ($keys->contains($key)) {
            return $userActions;
        }

        $userAction[$pid] = static::normalizeUserAction($post, $upvoted, $downvoted);

        $userActions->put($key, $userAction);

        return $userActions;
    }

    private static function normalizeUserAction($post, $upvoted, $downvoted)
    {
        return (object) [
            'votes'       => $post->votes,
            'upvotes'     => $post->upvotes,
            'upvoted'     => $upvoted,
            'downvotes'   => $post->downvotes,
            'downvoted'   => $downvoted,
        ];
    }

    private static function updateVotesForTopicOrPostToCache($post)
    {
        $topic = static::getTopicFromCacheById($post->tid);

        if (empty($topic)) {
            return;
        }

        if ($post->pid === $topic->mainPid) {
            $topic->votes = $post->votes;
            $topic->upvotes = $post->upvotes;
            $topic->downvotes = $post->downvotes;

            return static::saveTopicsToCache([$topic]);
        }

        $posts = collect($topic->posts);
        $posts->transform(function ($item) use ($post) {
            if ($post->pid !== $item->pid) {
                return $item;
            }

            $item->votes = $post->votes;
            $item->upvotes = $post->upvotes;
            $item->downvotes = $post->downvotes;

            return $item;
        })->unique('pid');

        $topic->posts =  $posts;
        static::saveTopicsToCache([$topic]);
    }

    private static function getUserActionsFromCache()
    {
        $key = static::getUserActionsKey();
        return static::hasKeyInCache($key) ? collect(static::getFromCache($key)) : collect([]);
    }

    private static function getTopicFromCacheById($tid)
    {
        $topicsFromCache = static::getTopicsFromCache();

        return $topicsFromCache->where('tid', $tid)->first();
    }

    private static function getUserInfoByUid($uid)
    {
        return User::join('nodebb_users', 'nodebb_users.user_id', 'users.id')
            ->with('visibleSettings')
            ->where('nodebb_users.nodebb_user_id', $uid)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type', 'nodebb_users.nodebb_user_id')
            ->first();
    }

    private static function getUserIdLogged()
    {
        return Auth::check() ? Auth::id() : Auth::guard('api')->id();
    }

    private static function saveAndSetExpireDataToCache($key, $data)
    {
        static::deleteCacheWithKey($key);
        static::saveToCache($key, collect($data));
        static::setExpire($key, static::CACHE_TIME_LIVE);
    }

    private static function getRedisConnection()
    {
        return Consts::RC_USER_FORUMS;
    }

    private static function getUserTopicsKey()
    {
        return 'user_topics';
    }

    private static function getTopicsKey()
    {
        return 'topics';
    }

    private static function getUserActionsKey()
    {
        return 'user_actions';
    }

    public static function removeCache($tid)
    {
        static::removeCacheTopics($tid);
        static::removeCacheUserTopics($tid);
    }

    private static function removeCacheTopics($tid)
    {
        $topicsFromCache = static::getTopicsFromCache();

        // if ($topicsFromCache->isEmpty() || empty($topicsFromCache[$tid])) {
        //     return;
        // }

        $topicsFromCache->forget($tid);
        static::saveAndSetExpireDataToCache(static::getTopicsKey(), $topicsFromCache);
    }

    private static function removeCacheUserTopics($tid)
    {
        $userId = static::getUserIdLogged();
        $userTopics = static::getUserTopicsFromCache();

        if (!$userTopics->isNotEmpty() || empty($userTopics[$userId])) {
            return;
        }

        $userTopics[$userId] = $userTopics[$userId]->forget($tid);
        static::saveAndSetExpireDataToCache(static::getUserTopicsKey(), $userTopics);
    }
}
