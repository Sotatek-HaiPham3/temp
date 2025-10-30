<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    protected $table = 'communities';

    use SoftDeletes;

    protected $fillable = [
        'mattermost_channel_id',
        'name',
        'slug',
        'description',
        'photo',
        'gallery_id',
        'status',
        'total_users',
        'total_request',
        'leader_count',
        'member_count',
        'total_rooms',
        'total_rooms_size',
        'total_rooms_user',
        'is_private',
        'creator_id',
        'inactive_at',
        'allow_share_screen'
    ];

    public static function getChannelByMattermostChannelId($mattermostChannelId)
    {
        return Community::where('mattermost_channel_id', $mattermostChannelId)->first();
    }

    public function communityMember()
    {
        return $this->hasMany('App\Models\CommunityMember', 'community_id', 'id');
    }
}
