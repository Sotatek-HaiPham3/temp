<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceDiary extends Model
{

    protected $fillable = [
        'channel_id',
        'hash',
        'caller_id',
        'receiver_id',
        'status',
        'started_time',
        'ended_time'
    ];
}
