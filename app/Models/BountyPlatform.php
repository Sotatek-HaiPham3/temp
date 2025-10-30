<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyPlatform extends Model
{
    protected $fillable = [
        'bounty_id',
        'platform_id'
    ];
}
