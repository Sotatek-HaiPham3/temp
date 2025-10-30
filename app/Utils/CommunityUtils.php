<?php

namespace App\Utils;

use App\Models\User;
use App\Models\Community;
use App\Models\CommunityMember;
use Illuminate\Support\Str;
use Mattermost;
use Cache;
use App\Consts;
use App\Utils;
use App\Traits\RedisTrait;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Auth;

class CommunityUtils {

    use RedisTrait;

    private static function getMoreChannels($params = [])
    {
        $mattermostChannels = static::getChannelsMattermost();
        $newChannels = static::getNewChannels($mattermostChannels, $params);

        if ($newChannels->isEmpty()) {
            logger()->info('=================no newChannels');
            return [];
        }

        return static::attachChannelsInfo($newChannels);
    }

    private static function attachChannelsInfo($channels)
    {
        $channelIds = $channels->map(function ($channel) {
            return $channel->id;
        });

        $channelMembers = CommunityMember::whereIn('community_id', $channelIds)->get();

        logger()->info('========================BUILD CHANNELS======================');

        $data = static::buildChannels([
            'channels'              => $channels,
            'channel_members'       => $channelMembers
        ]);

        return $data;
    }

    private static function buildChannels($params)
    {
        $data = [];

        $channels           = array_get($params, 'channels');
        $channelMembers     = array_get($params, 'channel_members');

        $channels->each(function ($channel) use (&$data, $channelMembers) {
            $channel->channel_id = $channel->mattermost_channel_id;
            unset($channel->mattermost_channel_id);

            $data[] = $channel;
        });

        return $data;
    }

    private static function getChannelsMattermost()
    {
        $key = static::getMattermostChannelsKey();
        $mattermostChannels = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        if (!$mattermostChannels->isEmpty()) {

            $newMattermostChannelKey = static::getMattermostNewChannelsKey();
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

        $mattermostChannels = Mattermost::getPublicChannels();

        $mattermostChannels = collect($mattermostChannels)->sortByDesc('last_post_at');

        static::saveToCache($key, $mattermostChannels);

        return $mattermostChannels;
    }

    private static function getNewChannels($mattermostChannels, $params = [])
    {
        $key = static::getAllChannelsKey();
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

        $addMoreChannels = Community::whereIn('mattermost_channel_id', $mattermostChannelIds)->get();

        if ($addMoreChannels->isEmpty()) {
            logger()->info('=================no addMoreChannels');
            return collect([]);
        }

        // merge data and sortDesc by last_post_at
        $addMoreChannels = static::mergeAndSortDescLastPostAtChannels($addMoreChannels, $mattermostChannels);

        return $addMoreChannels->sortByDesc('last_post_at');
    }

    public static function addNewMember($mattermostChannelId, $member)
    {
        $key = static::getMembersKey();
        $cacheData = static::hasKeyInCache($key) ? static::getFromCache($key) : [];

        $user = static::getUserDataToCache($member->user_id);
        $cacheData[$mattermostChannelId][$user->id] = static::modifyUserDetail($user);

        static::saveToCache($key, $cacheData);

        return $cacheData[$mattermostChannelId];
    }

    private static function getUsersInfo($userIds)
    {
        return DB::table('users')->join('mattermost_users', 'mattermost_users.user_id', 'users.id')
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->whereIn('users.id', $userIds)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type', 'users.status', 'users.deleted_at',
                'mattermost_users.mattermost_user_id AS chat_user_id', 'user_settings.online')
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
            'id' => $user->id,
            'avatar' => $user->avatar,
            'sex' => $user->sex,
            'username' => $user->username,
            'user_type' => $user->user_type,
            'chat_user_id' => $user->chat_user_id,
            'online' => $user->online,
            'status' => $user->status,
            'deleted_at' => $user->deleted_at
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

    private static function getAllChannelsKey()
    {
        return 'all_communities';
    }

    private static function getMattermostChannelsKey()
    {
        return 'mattermost_communities';
    }

    private static function getMattermostNewChannelsKey()
    {
        return 'mattermost_new_communities';
    }

    private static function getMembersKey()
    {
        return 'all_members_communities';
    }

    private static function getRedisConnection()
    {
        return Consts::RC_COMMUNITIES;
    }

    /*
     * Add new channel into Cache
     */
    public static function addNewChannel($mattermostChannel)
    {
        /*
         * Save new mattermost channel into temporary
         */
        $newMattermostChannelKey = static::getMattermostNewChannelsKey();
        $newMattermostChannels = static::hasKeyInCache($newMattermostChannelKey) ? static::getFromCache($newMattermostChannelKey) : collect([]);
        $newMattermostChannels->push($mattermostChannel);
        static::saveToCache($newMattermostChannelKey, $newMattermostChannels);

        /*
         * Adding immediately new mattermost channel into cache. It can't, we will user new_mattermost_channel on temporary to backup.
         */
        // Add new channel to mattermost
        $mattermostChannelKey = static::getMattermostChannelsKey();
        $mattermostChannels = static::hasKeyInCache($mattermostChannelKey) ? static::getFromCache($mattermostChannelKey) : collect([]);

        logger()->info('==================ADD_NEW_COMMUNITY', ['new' => $mattermostChannel]);

        $mattermostChannels->push($mattermostChannel)
            ->unique('id')
            ->sortByDesc('last_post_at');

        static::saveToCache($mattermostChannelKey, $mattermostChannels);
    }

    /*
     * Update last_post_at, last_post_message when having a new post.
     */
    public static function updateChannelWhenNewPost($newPost)
    {
        $config = [
            'fields' => [
                'last_post_at'
            ]
        ];

        $internalChannelKey = static::getAllChannelsKey();
        static::updateChannelDetail($internalChannelKey, $newPost, $config);

        $mattermostChannelKey = static::getMattermostChannelsKey();
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

            return $channel;
        });

        static::saveToCache($cacheKey, $channels->sortByDesc('last_post_at'));
    }

    /*
     * Update user info: avatar, username, ... for all members of any channels
    */
    public static function updateChannelMembers($mattermostChannelId, $user)
    {
        $key = static::getMembersKey();
        $mapUsers = static::hasKeyInCache($key) ? static::getFromCache($key) : [];

        $user = static::getUserDataToCache($user->id);
        $mapUsers[$mattermostChannelId][$user->id] = static::modifyUserDetail($user);
        static::saveToCache($key, $mapUsers);
    }

    public static function updateAllChannelMembers($userId)
    {
        $key = static::getMembersKey();
        $mapUsers = static::hasKeyInCache($key) ? static::getFromCache($key) : [];
        $existsMattermostChannelIdsInCache = collect($mapUsers)->keys();

        if ($existsMattermostChannelIdsInCache->isEmpty()) {
            return;
        }

        $user = static::getUserDataToCache($userId);
        $existsMattermostChannelIdsInCache->each(function ($mattermostChannelId) use (&$mapUsers, $user) {
            $mapUsers[$mattermostChannelId][$user->id] = static::modifyUserDetail($user);
        });

        static::saveToCache($key, $mapUsers);
    }

    public static function getChannelDetail($mattermostChannelId, $isProcessed = false)
    {
        $channels = static::getMoreChannels();

        $key = static::getAllChannelsKey();
        $data = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);

        $channel = $data->merge($channels)->filter(function ($channel) use ($mattermostChannelId) {
            return $channel->channel_id === $mattermostChannelId;
        })->first();

        if ($channel) {
            return $channel;
        }

        return static::tryGettingChannelDetail($mattermostChannelId, $isProcessed);
    }

    private static function tryGettingChannelDetail($mattermostChannelId, $isProcessed = false)
    {
        if ($isProcessed) {
            return;
        }

        $user = Auth::user();
        logger()->info('==================TRY_GETTING_CHANNEL_DETAIL', [
            'user_id' => $user->id,
            'mattermost_channel_id' => $mattermostChannelId
        ]);

        $isProcessed = true;

        $key = static::getMattermostChannelsKey();

        $mattermostUserId = $user->mattermostUser->mattermost_user_id;
        $mattermostChannels = Mattermost::getPublicChannels($mattermostUserId);

        $mattermostChannels = collect($mattermostChannels)->sortByDesc('last_post_at');

        static::saveToCache($key, $mattermostChannels);

        return static::getChannelDetail($mattermostChannelId, $isProcessed);
    }

    public static function getChannelMembers($mattermostChannelId)
    {
        $key = static::getMembersKey();
        $cacheData = static::hasKeyInCache($key) ? static::getFromCache($key) : collect([]);
        return collect(collect($cacheData)->get($mattermostChannelId));
    }

    public static function buildChannelSlug($name) {
        $slugName = Str::slug($name);
        $checkSlug = Community::where('slug', $slugName)->exists();
        if(!$checkSlug){
            return $slugName;
        }
        $numericalPrefix = 1;

        while(true){
            //Check if Slug with final prefix exists.
            $newSlug = $slugName . "-" . $numericalPrefix++; //new Slug with incremented Slug Numerical Prefix
            $newSlug = Str::slug($newSlug); //String Slug

            $checkSlug = Community::where('slug', $newSlug)->exists(); //Check if already exists in DB

            if(!$checkSlug){
                //There is not more coincidence. Finally found unique slug.
                return $newSlug;
                break;
            }
        }
    }

    public static function buildChannelType($isPrivate) {
        return ($isPrivate) ? 'P' : 'O'; // O: Open, P: Private
    }
}
