<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomQuestion extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'rejector_id',
        'question',
        'status'
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'username', 'sex', 'avatar');
    }
}
