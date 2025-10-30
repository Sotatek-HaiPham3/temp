<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomSetting extends Model
{
    protected $fillable = [
        'room_id',
        'allow_ask_question'
    ];
}
