<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPhoto extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'type',
        'is_active'
    ];
}
