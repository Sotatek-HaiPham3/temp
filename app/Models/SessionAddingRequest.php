<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAddingRequest extends Model
{
    protected $fillable = [
        'session_id',
        'quantity',
        'escrow_balance',
        'status'
    ];
}
