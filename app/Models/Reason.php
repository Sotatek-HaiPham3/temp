<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    protected $fillable = [
        'object_type',
        'reason_type',
        'content',
        'static_reason',
        'object_id'
    ];
}
