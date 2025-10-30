<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Channel extends Model
{
    protected $table = 'channels';

    protected $fillable = [
        'mattermost_channel_id',
        'channel_tag'
    ];

    const DELIMITER_CHANNEL_TAG = '_';

    public static function getChannelByMattermostChannelId($mattermostChannelId)
    {
        return Channel::where('mattermost_channel_id', $mattermostChannelId)->first();
    }

    public static function getChatChannel($oppositeUserId)
    {
        $channelTags = [
            sprintf('%s_%s', Auth::id(), $oppositeUserId),
            sprintf('%s_%s', $oppositeUserId, Auth::id())
        ];

        return Channel::whereIn('channel_tag', $channelTags)->first();
    }

    public static function buildChannelTag($userIds)
    {
        return join(static::DELIMITER_CHANNEL_TAG, $userIds);
    }

    public function getUsersOnChannel()
    {
        return explode(static::DELIMITER_CHANNEL_TAG, $this->channel_tag);
    }

    public function getOppositeUserId($userId = null)
    {
        $delimiter = static::DELIMITER_CHANNEL_TAG . '|' . static::DELIMITER_CHANNEL_TAG;
        $userId = $userId ?: Auth::id();

        $pattern = "({$userId}{$delimiter}{$userId})";
        return preg_replace($pattern, '', $this->channel_tag);
    }

    public static function getChannelBySenderIdAndReceiverId($senderId, $receiverId)
    {
        $channelTags = [
            sprintf('%s_%s', $senderId, $receiverId),
            sprintf('%s_%s', $receiverId, $senderId)
        ];

        return Channel::whereIn('channel_tag', $channelTags)->first();
    }
}
