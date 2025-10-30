<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomReport extends Model
{
    protected $table = 'room_reports';

    protected $fillable = [
        'room_id',
        'reporter_id',
        'reason_id',
        'details',
        'status'
    ];
}
