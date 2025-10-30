<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectingTasking extends Model
{

    protected $fillable = [
        'user_id',
        'tasking_id',
        'quantity',
        'collected_at'
    ];
}
