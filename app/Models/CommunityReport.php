<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityReport extends Model
{
    protected $table = 'community_reports';

    protected $fillable = [
        'community_id',
        'reporter_id',
        'reason_id',
        'details',
        'status' // processing, processed
    ];
}
