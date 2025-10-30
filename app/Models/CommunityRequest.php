<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityRequest extends Model
{
    protected $table = 'community_requests';

    protected $fillable = [
        'user_id',
        'community_id',
        'message',
        'status' // created, accepted, cancel
    ];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'email', 'username', 'sex', 'avatar', 'status');
    }
}
