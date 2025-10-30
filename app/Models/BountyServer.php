<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyServer extends Model
{
    protected $fillable = [
        'bounty_id',
        'game_server_id'
    ];
}
