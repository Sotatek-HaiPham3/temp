<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomRequest extends Model
{
    protected $table = 'room_requests';

    protected $fillable = [
        'user_id',
        'room_id',
        'message',
        'status'
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'email', 'username', 'sex', 'avatar');
    }
}
