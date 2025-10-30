<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomUserReport extends Model
{
    protected $table = 'room_user_reports';

    protected $fillable = [
        'room_id',
        'reported_user_id',
        'reporter_id',
        'reason_id',
        'details',
        'status'
    ];
}
