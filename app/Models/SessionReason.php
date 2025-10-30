<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionReason extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'session_id',
        'reason_id',
        'data'
    ];
}
