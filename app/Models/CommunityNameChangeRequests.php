<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityNameChangeRequests extends Model
{
    protected $table = 'community_name_change_requests';

    protected $fillable = [
        'community_id',
        'request_user_id',
        'reason_id',
        'old_name',
        'new_name',
        'status' // pending, canceled, approved, rejected
    ];
}
