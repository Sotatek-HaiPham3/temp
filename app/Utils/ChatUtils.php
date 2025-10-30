<?php

namespace App\Utils;

use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelMember;
use Mattermost;
use Cache;
use App\Consts;
use App\Utils;
use App\Traits\RedisTrait;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ChatUtils {

    use RedisTrait;

    public static function getChannelsForUser(User $user, $params = [])
    {
        $key = static::getUserAllChannelsKey($user->id);
        $data = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        $needMoreData = static::getMoreChannelForUser($user, $params);

        $data = $data->merge($needMoreData)
            ->unique('channel_id')
            ->sortByDesc('last_post_at');

        static::saveToCache($key, $data);

        $data = $data->filter(function ($item) {
                return $item->last_post_at;
            })
            ->when(!empty($params['channel_ids']), function ($collection) use ($params) {
                return $collection->filter(function ($item) use ($params) {
                    return in_array($item->channel_id, $params['channel_ids']);
                });
            });

        $limit = (empty($params['limit']) || $params['limit'] <= 0) ? Consts::DEFAULT_PER_PAGE : $params['limit'];
        $paginator = Utils::convertArrayToPagination($data, $limit);

        $paginator = static::loadUserInfoLatest($user, $paginator);

        return $paginator;
    }

    public static function getChannelsExistsUnreadMessageForUser(User $user)
    {
        $key = static::getUserAllChannelsKey($user->id);
        $channels = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        return $channels->filter(function ($channel) {
            $msgCount = $channel->unread_messages->msg_count ?? 0;

            return $msgCount > 0;
        });
    }

    private static function getMoreChannelForUser(User $user, $params = [])
    {
        $mattermostUserId = $user->mattermostUser->mattermost_user_id;
        $userId = $user->id;

        $mattermostChannels = static::getChannelsMattermost($userId, $mattermostUserId);
        $newChannels = static::getUserNewChannels($userId, $mattermostChannels, $params);

        if ($newChannels->isEmpty()) {
            logger()->info('=================no newChannels');
            return [];
        }

        return static::attachChannelsInfo($userId, $mattermostUserId, $newChannels);
    }

    private static function attachChannelsInfo($userId, $mattermostUserId, $channels)
    {
        $channelIds = $channels->map(function ($channel) {
            return $channel->id;
        });

        $channelMembers = ChannelMember::whereIn('channel_id', $channelIds)->get();

        $oppositeUserIds = $channels->map(function ($channel) use ($userId) {
                return $channel->getOppositeUserId($userId);
            })
            ->values()
            ->toArray();

        $oppositeUsers = static::getOppositeUsers($oppositeUserIds);

        logger()->info('========================BUILD CHANNELS======================');

        $data = static::buildChannels([
            'user_id'               => $userId,
            'channels'              => $channels,
            'opposite_users'        => $oppositeUsers,
            'mattermost_user_id'    => $mattermostUserId,
            'channel_members'       => $channelMembers
        ]);

        return $data;
    }

    private static function buildChannels($params)
    {
        $data = [];

        $channels           = array_get($params, 'channels');
        $userId             = array_get($params, 'user_id');
        $oppositeUsers      = array_get($params, 'opposite_users');
        $mattermostUserId   = array_get($params, 'mattermost_user_id');
        $channelMembers     = array_get($params, 'channel_members');

        $channels->each(function ($channel) use (&$data, $userId, $oppositeUsers, $mattermostUserId, $channelMembers) {
            // ignore channel that doesn't belong to the user.
            if (!in_array($userId, $channel->getUsersOnChannel())) {
                return;
            }

            $oppositeUserId = $channel->getOppositeUserId($userId);

            // Just make sure that always has oppositeUserIn in cache
            if (empty($oppositeUsers[$oppositeUserId])) {
                $oppositeUsers = static::getOppositeUsers([$oppositeUserId]);
            }
            $channel->user = $oppositeUsers[$oppositeUserId];

            $channel->last_post = static::getLastPost($channel->mattermost_channel_id);

            $channel->unread_messages = static::getUnreadMessages($userId, $mattermostUserId, $channel->mattermost_channel_id);

            $channel->current_member = $channelMembers->filter(function ($channelMember) use ($userId, $channel) {
                    return $channelMember->user_id === $userId && $channelMember->channel_id === $channel->id;
                })
                ->map(function ($channelMember) use ($mattermostUserId) {
                    $channelMember->chat_user_id = $mattermostUserId;
                    return $channelMember;
                })
                ->first();

            $channel->is_blocked = $channelMembers->contains(function ($channel) use ($userId, $oppositeUserId) {
                return $channel->is_blocked && in_array($channel->user_id, [$userId, $oppositeUserId]);
            });

            $channel->channel_id = $channel->mattermost_channel_id;
            unset($channel->mattermost_channel_id);

            $data[] = $channel;
        });

        return $data;
    }

    private static function getChannelsMattermost($userId, $mattermostUserId)
    {
        $key = static::getMattermostChannelsKey($userId);
        $mattermostChannels = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        if (!$mattermostChannels->isEmpty()) {

            $newMattermostChannelKey = static::getMattermostNewChannelsKey($userId);
            $newMattermostChannels = static::hasKeyInCache($newMattermostChannelKey) ? static::getFromCache($newMattermostChannelKey) : collect([]);

            if ($newMattermostChannels->isEmpty()) {
                return $mattermostChannels;
            }

            $mattermostChannels = $mattermostChannels->merge($newMattermostChannels)
                ->unique('id')
                ->sortByDesc('last_post_at');

            // empty cache new_mattermost_channels
            static::saveToCache($newMattermostChannelKey, collect([]));
            static::saveToCache($key, $mattermostChannels);

            return $mattermostChannels;
        }

        $mattermostChannels = Mattermost::getChannelsForUser($mattermostUserId);

        $mattermostChannels = collect($mattermostChannels)->sortByDesc('last_post_at');

        static::saveToCache($key, $mattermostChannels);

        return $mattermostChannels;
    }

    private static function getUserNewChannels($userId, $mattermostChannels, $params = [])
    {
        $key = static::getUserAllChannelsKey($userId);
        $channels = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        $existsMattermostChannelIdsInCache = $channels->map(function ($channel) {
                return $channel->channel_id;
            })
            ->toArray();

        // Get new mattermost channel that doesn't exist in cache.
        $mattermostChannelIds = $mattermostChannels->filter(function ($channel) use ($existsMattermostChannelIdsInCache) {
                return !in_array($channel->id, $existsMattermostChannelIdsInCache);
            })
            ->pluck('id');

        // no new mattermost channel
        if (empty($mattermostChannelIds)) {
            logger()->info('=================no new mattermost channel');
            return collect([]);
        }

        $addMoreChannels = Channel::whereIn('mattermost_channel_id', $mattermostChannelIds)->get();

        if ($addMoreChannels->isEmpty()) {
            logger()->info('=================no addMoreChannels');
            return collect([]);
        }

        // merge data and sortDesc by last_post_at
        $addMoreChannels = static::mergeAndSortDescLastPostAtChannels($addMoreChannels, $mattermostChannels);

        return $addMoreChannels->sortByDesc('last_post_at');
    }

    /*
     * Expect data:
     *  {
     *      user_id: { id: 11111, avatar: 2222, sex: 1, username: 23232, user_type: 1},
     *      user_id: { id: 11111, avatar: 2222, sex: 1, username: 23232, user_type: 1},
     *  }
     */
    private static function getOppositeUsers($userIds)
    {
        $key = static::getMembersKey();
        $cacheData = static::hasKeyInCache($key) ? static::getFromCache($key) : [];

        $noExistsUserIdInCache = empty($cacheData) ? $userIds : collect($userIds)->filter(function ($userId) use ($cacheData) {
            return empty($cacheData[$userId]);
        });

        if (collect($noExistsUserIdInCache)->isEmpty()) {
            return $cacheData;
        }

        $members = static::getUsersInfo($noExistsUserIdInCache)
            ->concat($cacheData)
            ->mapWithKeys(function ($item) {
                $user = (object) $item;
                return [$user->id => static::modifyUserDetail($user)];
            })
            ->all();

        static::saveToCache($key, $members);

        return $members;
    }

    private static function getUsersInfo($userIds)
    {
        return DB::table('users')->join('mattermost_users', 'mattermost_users.user_id', 'users.id')
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->whereIn('users.id', $userIds)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type',
                'mattermost_users.mattermost_user_id AS chat_user_id', 'user_settings.online', 'user_rankings.ranking_id')
            ->get()
            ->map(function ($user) { return (object) $user; });
    }

    public static function getUserDataToCache($userId)
    {
        return static::getUsersInfo([$userId])->first();
    }

    private static function modifyUserDetail($user)
    {
        return [
            'id'                    => $user->id,
            'avatar'                => $user->avatar,
            'sex'                   => $user->sex,
            'username'              => $user->username,
            'user_type'             => $user->user_type,
            'chat_user_id'          => $user->chat_user_id,
            'online'                => $user->online,
            'ranking_id'            => $user->ranking_id
        ];
    }

    private static function getLastPost($mattermostChannelId)
    {
        $result = Mattermost::getPostsForChannel($mattermostChannelId, ['per_page' => 1]);

        $lastPost = collect($result->posts)->first();

        return [
            'message' => $lastPost ? $lastPost->message : null,
            'props' => $lastPost ? $lastPost->props : []
        ];
    }

    private static function getUnreadMessages($userId, $mattermostUserId, $mattermostChannelId)
    {
        // Just make sure that the mattermostUserId can't blank.
        if (!$mattermostUserId) {
            $mattermostUserId = User::find($userId)->mattermostUser->mattermost_user_id;
        }

        $unreadMessages = Mattermost::getUnreadMessages($mattermostUserId, $mattermostChannelId);
        return (object) [
            'msg_count' => $unreadMessages->msg_count
        ];
    }

    private static function mergeAndSortDescLastPostAtChannels($channels, $mattermostChannels)
    {
        $mapMattermostChannels = $mattermostChannels
            ->mapWithKeys(function ($item) { return [$item->id => $item]; })
            ->all();

        return $channels->filter(function ($channel) use ($mapMattermostChannels) {
                return !!$mapMattermostChannels[$channel->mattermost_channel_id];
            })
            ->map(function ($channel) use ($mapMattermostChannels) {
                $mattermostChannel = $mapMattermostChannels[$channel->mattermost_channel_id];

                $channel->channel_id = $channel->mattermost_channel_id;
                $channel->last_post_at = $mattermostChannel->last_post_at;

                return $channel;
            })
            ->sortByDesc('last_post_at');
    }

    private static function getUserChannelsKey($userId)
    {
        return sprintf('channels_user_%s', $userId);
    }

    private static function getUserAllChannelsKey($userId)
    {
        return sprintf('%s.all_channels', static::getUserChannelsKey($userId));
    }

    private static function getMattermostChannelsKey($userId)
    {
        return sprintf('%s.mattermost_channels', static::getUserChannelsKey($userId));
    }

    private static function getMattermostNewChannelsKey($userId)
    {
        return sprintf('%s.mattermost_new_channels', static::getUserChannelsKey($userId));
    }

    private static function getMembersKey()
    {
        return 'all_memebers_channels';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_USER_CHANNELS;
    }

    /*
     * Add new channel into Cache
     */
    public static function addNewChannel($user, $mattermostChannel)
    {
        /*
         * Save new mattermost channel into temporary
         */
        $newMattermostChannelKey = static::getMattermostNewChannelsKey($user->id);
        $newMattermostChannels = static::hasKeyInCache($newMattermostChannelKey) ? static::getFromCache($newMattermostChannelKey) : collect([]);
        $newMattermostChannels->push($mattermostChannel);
        static::saveToCache($newMattermostChannelKey, $newMattermostChannels);

        /*
         * Adding immediately new mattermost channel into cache. It can't, we will user new_mattermost_channel on temporary to backup.
         */
        // Add new channel to mattermost
        $mattermostChannelKey = static::getMattermostChannelsKey($user->id);
        $mattermostChannels = static::hasKeyInCache($mattermostChannelKey) ? static::getFromCache($mattermostChannelKey) : collect([]);

        logger()->info('==================ADD_NEW_CHANNEL', ['new' => $mattermostChannel]);

        $mattermostChannels->push($mattermostChannel)
            ->unique('id')
            ->sortByDesc('last_post_at');

        static::saveToCache($mattermostChannelKey, $mattermostChannels);
    }

    /*
     * Update last_post_at when the user views channel or read new message.
     */
    public static function updateChannelWhenViewedChannel($user, $mattermostChannelId)
    {
        $config = [
            'fields' => [ 'unread_messages' ]
        ];

        $data = (object) [
            'channel_id'    => $mattermostChannelId
        ];

        $internalChannelKey = static::getUserAllChannelsKey($user->id);
        static::updateChannelDetail($internalChannelKey, $data, $config);
    }

    /*
     * Update last_post_at, last_post_message when having a new post.
     */
    public static function updateChannelWhenNewPost($user, $newPost)
    {
        $config = [
            'fields' => [
                'last_post_at',
                'last_post',
                'unread_messages'
            ]
        ];

        $internalChannelKey = static::getUserAllChannelsKey($user->id);
        static::updateChannelDetail($internalChannelKey, $newPost, $config);

        $mattermostChannelKey = static::getMattermostChannelsKey($user->id);
        static::updateChannelDetail(
            $mattermostChannelKey,
            $newPost,
            array_merge($config, [
                'is_mattermost_channel' => true
            ])
        );
    }

    private static function updateChannelDetail($cacheKey, $data, $config = [])
    {
        $channels = static::hasKeyInCache($cacheKey) ? static::getFromCache($cacheKey) : [];

        if (empty($channels)) {
            return;
        }

        $isMattermostChannel = !empty($config['is_mattermost_channel']);
        $fields = array_get($config, 'fields', []);

        $channels->transform(function ($channel) use ($data, $isMattermostChannel, $fields, $config, $cacheKey) {
            $fieldChannelId = $isMattermostChannel ? 'id' : 'channel_id';

            if ($channel->{$fieldChannelId} !== $data->channel_id) {
                return $channel;
            }


            if (in_array('last_post_at', $fields)) {
                $channel->last_post_at = $data->create_at;
            }

            if (in_array('last_post', $fields)) {
                $channel->last_post = [
                    'message' => $data->message,
                    'props' => $data->props
                ];
            }

            if (in_array('unread_messages', $fields) && !$isMattermostChannel) {
                $user = (object) $channel->current_member;
                $channel->unread_messages = static::getUnreadMessages($user->user_id, $user->chat_user_id, $data->channel_id);
            }

            return $channel;
        });

        static::saveToCache($cacheKey, $channels->sortByDesc('last_post_at'));
    }

    /*
     * Update channel setting: blocked, muted.
     */
    public static function updateChannelSetting($user, $channelMember, $isBlocked = null)
    {
        $userId = $user->id;
        $key = static::getUserAllChannelsKey($userId);

        $channels = static::hasKeyInCache($key) ? static::getFromCache($key) : [];

        if (empty($channels)) {
            return;
        }

        $channels->transform(function ($channel) use ($channelMember, $userId, $isBlocked) {
            if ($channel->id !== $channelMember->channel_id || $channelMember->user_id !== $userId) {
                return $channel;
            }

            $channel->current_member = $channelMember;

            // it means we have that parameter
            if (!is_null($isBlocked)) {
                $channel->is_blocked = $isBlocked;
            }

            return $channel;
        });

        static::saveToCache($key, $channels->sortByDesc('last_post_at'));
    }

    /*
     * Update user info: avatar, username, ... for all members of any channels
    */
    public static function updateChannelMembers($user)
    {
        $key = static::getMembersKey();
        $mapUsers = static::hasKeyInCache($key) ? static::getFromCache($key) : [];

        if (empty($mapUsers)) {
            return;
        }

        $mapUsers[$user->id] = static::modifyUserDetail($user);

        static::saveToCache($key, $mapUsers);
    }

    public static function getChannelDetail(User $user, $mattermostChannelId, $isProcessed = false)
    {
        $channels = static::getMoreChannelForUser($user);

        $key = static::getUserAllChannelsKey($user->id);
        $data = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        $channel = $data->merge($channels)->filter(function ($channel) use ($mattermostChannelId) {
            return $channel->channel_id === $mattermostChannelId;
        })->first();

        if ($channel) {
            return $channel;
        }

        return static::tryGettingChannelDetail($user, $mattermostChannelId, $isProcessed);
    }

    private static function tryGettingChannelDetail(User $user, $mattermostChannelId, $isProcessed = false)
    {
        if ($isProcessed) {
            return;
        }

        logger()->info('==================TRY_GETTING_CHANNEL_DETAIL', [
            'user_id' => $user->id,
            'mattermost_channel_id' => $mattermostChannelId
        ]);

        $isProcessed = true;

        $key = static::getMattermostChannelsKey($user->id);

        $mattermostUserId = $user->mattermostUser->mattermost_user_id;
        $mattermostChannels = Mattermost::getChannelsForUser($mattermostUserId);

        $mattermostChannels = collect($mattermostChannels)->sortByDesc('last_post_at');

        static::saveToCache($key, $mattermostChannels);

        return static::getChannelDetail($user, $mattermostChannelId, $isProcessed);
    }

    private static function loadUserInfoLatest(User $user, $paginator)
    {
        $membersKey = static::getMembersKey();
        $mapUsers = static::hasKeyInCache($membersKey) ? static::getFromCache($membersKey) : collect([]);

        // Update user avatar for all_channels in cache
        $allChannelsKey = static::getUserAllChannelsKey($user->id);
        $allChannels = static::hasKeyInCache($allChannelsKey) ? static::getFromCache($allChannelsKey) : collect([]);

        $allChannels = $allChannels->map(function ($channel) use (&$mapUsers) {
            $user = (object) $channel->user;

            if (empty($mapUsers[$user->id])) {
                return $channel;
            }

            $userValues = array_values($mapUsers[$user->id]);
            if (static::isInvalidData($userValues)) {
                $newUserInfo = (object) static::getUsersInfo([$user->id])->first();
                $mapUsers[$user->id] = $newUserInfo ? static::modifyUserDetail($newUserInfo) : $mapUsers[$user->id];
            }

            $channel->user = $mapUsers[$user->id];

            return $channel;
        });

        static::saveToCache($membersKey, $mapUsers);
        static::saveToCache($allChannelsKey, $allChannels);

        // Update user avatar for paginator.
        $paginator->getCollection()->transform(function ($channel) use ($mapUsers) {
            $user = (object) $channel->user;

            if (empty($mapUsers[$user->id])) {
                return $channel;
            }

            $channel->user = $mapUsers[$user->id];

            return $channel;
        });

        return $paginator;
    }

    private static function isInvalidData($data)
    {
        $invalidData = [null, ''];

        return collect($invalidData)->contains(function ($item) use ($data) {
            return in_array($item, $data);
        });
    }

    public static function updateUserInfo($user)
    {
        $key = static::getMembersKey();
        $cacheData = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        $userId = $user->id;
        $cacheData[$userId] = static::modifyUserDetail($user);

        static::saveToCache($key, $cacheData);

        return $user;
    }
}
