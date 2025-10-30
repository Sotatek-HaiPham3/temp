<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityUserReport extends Model
{
    protected $table = 'community_user_reports';

    protected $fillable = [
        'community_id',
        'reported_user_id',
        'reporter_id',
        'reason_id',
        'details',
        'status'
    ];
}
