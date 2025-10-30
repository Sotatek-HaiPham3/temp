<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodebbUser extends Model
{
    protected $table = 'nodebb_users';

    protected $fillable = [
        'user_id',
        'nodebb_user_id'
    ];
}
