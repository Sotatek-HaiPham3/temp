<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class RoomInvitation extends Model
{
    protected $table = 'room_invitations';

    protected $fillable = [
        'room_id',
        'sender_id',
        'receiver_id',
        'type',
        'status',
        'description'
    ];
}
