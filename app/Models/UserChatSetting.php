<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChatSetting extends Model
{
    protected $fillable = [
        'public',
        'connected',
        'user_has_money',
        'user_has_request'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id', 'id');
    }
}
