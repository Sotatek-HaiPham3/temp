<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsWhitelist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'country_code'
    ];
}
