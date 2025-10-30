<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMessageReport extends Model
{
    protected $table = 'community_message_reports';

    protected $fillable = [
        'community_id',
        'user_id',
        'mattermost_post_id',
        'reporter_id',
        'reason_id',
        'details',
        'status' // processing, processed
    ];
}
