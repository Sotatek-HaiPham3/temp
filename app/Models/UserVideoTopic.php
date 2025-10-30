<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVideoTopic extends Model
{
    protected $table = 'user_video_topics';

    protected $fillable = [
        'user_id',
        'video_id',
        'topic_id'
    ];

    public static function checkExistsTopicForVideo($videoId)
    {
        return static::where('video_id', $videoId)->value('topic_id');
    }

    public static function checkExistsVideoForTopic($tid)
    {
        return static::where('topic_id', $tid)->value('video_id');
    }
}
