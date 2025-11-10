<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MattermostUser extends Model
{
    protected $table = 'mattermost_users';

    protected $fillable = ['user_id', 'mattermost_user_id'];

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }
}
