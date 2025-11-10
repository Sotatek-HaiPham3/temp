<?php

namespace App\Http\Services;

use Auth;
use Nodebb;
use App\Consts;
use App\Models\UserVideoTopic;
use App\Models\User;
use App\Utils\NodebbUtils;
use App\Events\CommentCreated;
use App\Events\CommentUpdated;
use App\Events\TopicCreated;
use App\Events\TopicUpdated;
use App\Events\TopicDeleted;
use App\Events\UpvoteUpdated;
use App\Events\DownvoteUpdated;
use App\Exceptions\Reports\DeleteTopicHasRelatedEntityException;
use App\Jobs\TopicsStatisticJob;
use App\Utils;
use App\Traits\NotificationTrait;

class NodebbService
{
    use NotificationTrait;

    public function createTopic($data)
    {
        $topic = NodebbUtils::createTopic($data);
        event(new TopicCreated(['topic' => collect($topic)->first()]));

        return $topic;
    }

    public function deleteTopic($tid)
    {
        if (UserVideoTopic::checkExistsVideoForTopic($tid)) {
            throw new DeleteTopicHasRelatedEntityException();
        }

        Nodebb::deleteTopic($tid);
        NodebbUtils::removeCache($tid);
        event(new TopicDeleted(['tid' => $tid]));

        return 'ok';
    }

    public function createComment($tid, $username, $data)
    {
        $post = Nodebb::createComment($tid, $data);
        $post = NodebbUtils::modifyPost($post->payload);
        NodebbUtils::saveNewPostToCache($tid, $username, $post);
        $topic = NodebbUtils::getTopicById($tid, $username);
        event(new CommentCreated(['tid' => $tid, 'post' => NodebbUtils::modifySubPosts($topic, $post)]));

        $videoId = UserVideoTopic::checkExistsVideoForTopic($tid);
        $videoInfo = NodebbUtils::pullVideoInfo($videoId);
        $videoUserId = !empty($videoInfo['user']) ? $videoInfo['user']['user_id'] : null;

        if ($videoId && $videoUserId && $videoUserId != Auth::id()) {
            $notificationParams = [
                'user_id' => $videoInfo['user']['user_id'],
                'type' => Consts::NOTIFY_TYPE_VIDEO_COMMENT,
                'message' => Consts::NOTIFY_VIDEO_COMMENT,
                'props' => ['video' => array_get($videoInfo, 'title')],
                'data' => [
                    'video_id' => $videoId,
                    'topic_id' => $tid,
                    'user' => (object) ['id' => Auth::id()]
                ]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_VIDEO, $notificationParams);
        }

        TopicsStatisticJob::dispatch(Auth::user(), $tid)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);

        return $post;
    }

    public function upvote($tid, $pid, $username)
    {
        $data = Nodebb::vote($pid, ['delta' => 1]);
        NodebbUtils::updateUserActionsToCache($data->payload);

        event(new UpvoteUpdated(['tid' => $tid, 'data' => $data->payload]));
        $videoId = UserVideoTopic::checkExistsVideoForTopic($tid);
        if ($this->shouldFireNotification($videoId, $username, $tid, $pid)) {
            $videoInfo = NodebbUtils::pullVideoInfo($videoId);
            $notificationParams = [
                'user_id' => !empty($videoInfo['user']) ? $videoInfo['user']['user_id'] : null,
                'type' => Consts::NOTIFY_TYPE_VIDEO_VOTE,
                'message' => Consts::NOTIFY_VIDEO_VOTE_UP,
                'props' => ['video' => array_get($videoInfo, 'title')],
                'data' => [
                    'video_id' => $videoId,
                    'user' => (object) ['id' => Auth::id()]
                ]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_VIDEO, $notificationParams);
        }

        return $data;
    }

    public function downvote($tid, $pid, $username)
    {
        $data = Nodebb::vote($pid, ['delta' => -1]);
        NodebbUtils::updateUserActionsToCache($data->payload);
        event(new DownvoteUpdated(['tid' => $tid, 'data' => $data->payload]));

        $videoId = UserVideoTopic::checkExistsVideoForTopic($tid);
        if ($this->shouldFireNotification($videoId, $username, $tid, $pid)) {
            $videoInfo = NodebbUtils::pullVideoInfo($videoId);
            $notificationParams = [
                'user_id' => !empty($videoInfo['user']) ? $videoInfo['user']['user_id'] : null,
                'type' => Consts::NOTIFY_TYPE_VIDEO_VOTE,
                'message' => Consts::NOTIFY_VIDEO_VOTE_DOWN,
                'props' => ['video' => array_get($videoInfo, 'title')],
                'data' => [
                    'video_id' => $videoId,
                    'user' => (object) ['id' => Auth::id()]
                ]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_VIDEO, $notificationParams);
        }

        return $data;
    }

    public function unvote($tid, $pid, $username)
    {
        $data = Nodebb::unvote($pid);
        NodebbUtils::updateUserActionsToCache($data->payload);
        event(new DownvoteUpdated(['tid' => $tid, 'data' => $data->payload]));

        return $data;
    }

    public function getTopicsForUser($params)
    {
        return NodebbUtils::getTopicsForUser($params);
    }

    public function getPostsForTopic($params)
    {
        return NodebbUtils::getPostsForTopic($params);
    }

    public function getSubPostsForPost($params)
    {
        return NodebbUtils::getSubPostsForPost($params);
    }

    public function getPostsDetail($params)
    {
        $tid = array_get($params, 'tid');
        $posts = array_get($params, 'posts');
        $username = array_get($params, 'username');
        $isSubPost = array_get($params, 'isSubPost', Consts::FALSE);

        $posts = collect($posts)->mapWithKeys(function ($item) {
            $item['user'] = (object) $item['user'];
            return [$item['pid'] => (object) $item];
        });

        $posts = NodebbUtils::savePostsToCache($tid, $username, $posts->all());

        if ($isSubPost) {
            return NodebbUtils::getSubPostsForPost($params);
        }

        return NodebbUtils::getPostsForTopic($params);
    }

    private function shouldFireNotification($videoId, $username, $tid, $pid)
    {
        $topic = NodebbUtils::getTopicById($tid, $username);

        return $videoId && $topic->mainPid === $pid;
    }
}
