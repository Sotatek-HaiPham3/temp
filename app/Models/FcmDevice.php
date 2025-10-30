<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmDevice extends Model {

    protected $table = 'fcm_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'token',
    ];
}
