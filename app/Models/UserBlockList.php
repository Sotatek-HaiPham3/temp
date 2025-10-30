<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBlockList extends Model
{
    protected $table = 'user_blocklists';

    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'is_blocked'
    ];
    public function userInfo()
    {
        return $this->hasOne('App\Models\User', 'id', 'blocked_user_id')
            ->with(['visibleSettings'])
            ->select('id', 'username', 'avatar', 'last_time_active', 'level', 'sex', 'status', 'deleted_at');
    }

}
