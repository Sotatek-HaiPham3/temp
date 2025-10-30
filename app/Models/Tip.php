<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    protected $fillable = [
        'object_id',
        'sender_id',
        'receiver_id',
        'type',
        'memo',
        'tip'
    ];
}
