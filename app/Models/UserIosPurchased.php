<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserIosPurchased extends Model
{
    protected $table = 'user_ios_purchased';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'original_transaction_id',
        'product_id',
        'quantity',
        'purchased_at',
    ];
}
