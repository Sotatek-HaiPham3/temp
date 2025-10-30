<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAndroidPurchased extends Model
{
    protected $table = 'user_android_purchased';

    protected $fillable = [
        'user_id',
        'package_name',
        'product_id',
        'purchase_token',
        'quantity',
        'developer_payload',
        'purchase_time_millis',
    ];
}
