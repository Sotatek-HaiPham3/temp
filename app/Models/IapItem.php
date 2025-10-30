<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IapItem extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'platform',
        'description',
        'price',
        'coin',
        'is_actived'
    ];
}
