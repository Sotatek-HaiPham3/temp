<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevelMeta extends Model
{
    protected $table = 'user_levels_meta';

    protected $fillable = [
        'name',
        'level',
        'image'
    ];
}
