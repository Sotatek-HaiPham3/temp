<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewTag extends Model
{
    protected $fillable = [
        'key',
        'content',
        'is_active'
    ];
}
