<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class VoiceChatRoom extends Model
{
    protected $table = 'voice_chat_rooms';

    protected $fillable = [
        'user_id',
        'creator_id',
        'pinned',
        'game_id',
        'is_private',
        'type',
        'name',
        'title',
        'topic',
        'topic_id',
        'size',
        'current_size',
        'region',
        'code',
        'rules',
        'background_url',
        'status',
        'community_id',
    ];

    public function voiceChatRoomUser()
    {
        return $this->hasMany('App\Models\VoiceChatRoomUser', 'room_id', 'id')
            ->whereNull('ended_time');
    }

    public function usersInRoom()
    {
        return $this->hasMany('App\Models\VoiceChatRoomUser', 'room_id', 'id')
            ->whereNull('ended_time')
            ->with(['user']);
    }

    public function raiseHands()
    {
        return $this->hasMany('App\Models\RoomRequest', 'room_id', 'id')
            ->select('room_id', 'user_id')
            ->where('status', Consts::ROOM_REQUEST_STATUS_CREATED);
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'email', 'username', 'sex', 'avatar', 'user_type', 'is_vip');
    }

    public function host()
    {
        return $this->hasOne('App\Models\VoiceChatRoomUser', 'room_id', 'id')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->whereNull('ended_time')
            ->with(['user']);
    }

    public function category()
    {
        return $this->hasOne('App\Models\RoomCategory', 'game_id', 'game_id')
            ->with(['game']);
    }

    public function roomRequest()
    {
        return $this->hasMany('App\Models\RoomRequest', 'room_id', 'id');
    }

    public function community()
    {
        return $this->hasOne('App\Models\Community', 'id', 'community_id');
    }
}
