<?php

namespace App\Models;

use App\Consts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityMember extends Model
{
    protected $table = 'community_members';

    use SoftDeletes;

    protected $fillable = [
        'community_id',
        'user_id',
        'viewed_at',
        'invited_user_id',
        'role', // owner , leader , member
        'kicked_by'
    ];

    public function community()
    {
        return $this->hasOne('App\Models\Community', 'id', 'community_id');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')
            ->select('id', 'email', 'username', 'sex', 'avatar', 'user_type', 'is_vip');
    }
}
