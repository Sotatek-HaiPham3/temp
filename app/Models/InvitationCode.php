<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationCode extends Model
{
    protected $fillable = [
        'code',
        'taken_at'
    ];
}
