<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSocialNetwork extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'url',
        'social_id',
        'visible',
    ];
}
