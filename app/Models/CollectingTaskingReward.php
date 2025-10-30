<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectingTaskingReward extends Model
{

    protected $fillable = [
        'user_id',
        'tasking_reward_id'
    ];
}
