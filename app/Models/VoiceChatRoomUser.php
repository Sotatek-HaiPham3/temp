<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceChatRoomUser extends Model
{
    protected $table = 'voice_chat_room_users';

    protected $fillable = [
        'room_id',
        'user_id',
        'invited_user_id',
        'is_kicked',
        'eliminator_id',
        'type',
        'sid',
        'username',
        'started_time',
        'ended_time'
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'email', 'username', 'sex', 'avatar', 'user_type', 'is_vip');
    }

    public function voiceChatRoom()
    {
        return $this->hasOne('App\Models\VoiceChatRoom', 'id', 'room_id');
    }
}
