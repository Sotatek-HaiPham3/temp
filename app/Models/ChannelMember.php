<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelMember extends Model
{
    protected $table = 'channel_members';

    protected $fillable = [
        'channel_id',
        'user_id',
        'is_blocked',
        'is_muted',
        'viewed_at',
        'is_sent_message'
    ];

    public function channel()
    {
        return $this->hasOne('App\Models\Channel', 'id', 'channel_id');
    }

    public static function userHasSendedMessgae($channelId, $userId)
    {
        return static::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->value('is_sent_message');
    }
}
